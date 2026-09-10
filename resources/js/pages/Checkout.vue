<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import AppHeader from '@/components/AppHeader.vue'
import Icon from '@/components/Icon.vue'
import { useCart } from '@/lib/cart'
import { rp } from '@/lib/format'

const props = defineProps({ qrisAvailable: { type: Boolean, default: false } })

const cart = useCart()

const form = ref({ phone: '', customer_name: '', email: '', detail: '' })
const paymentMethod = ref('bank_transfer')

// Verifikasi WhatsApp (opsional) — cuma dibutuhkan buat auto-isi data dari order
// sebelumnya, atau buat nyimpen data sekarang. Checkout biasa tanpa verifikasi tetap bisa.
const otpSent = ref(false)
const otpCode = ref('')
const sendingOtp = ref(false)
const verifyingOtp = ref(false)
const phoneVerified = ref(false)
const saveProfile = ref(true)
const otpError = ref('')
const welcomeBack = ref(false)

async function sendOtp() {
  if (!form.value.phone) return
  sendingOtp.value = true
  otpError.value = ''
  try {
    await axios.post('/checkout/kirim-otp', { phone: form.value.phone })
    otpSent.value = true
  } catch (e) {
    otpError.value = e.response?.data?.message || 'Gagal mengirim kode.'
  } finally {
    sendingOtp.value = false
  }
}

async function verifyOtp() {
  if (!otpCode.value) return
  verifyingOtp.value = true
  otpError.value = ''
  try {
    const { data } = await axios.post('/checkout/verifikasi-otp', { phone: form.value.phone, code: otpCode.value })
    phoneVerified.value = true
    welcomeBack.value = data.has_delivered_order
    if (data.profile) {
      form.value.customer_name = data.profile.name || ''
      form.value.email = data.profile.email || ''
      const addr = data.profile.address
      if (addr?.lat && addr?.lng) {
        form.value.detail = addr.detail || ''
        location.value = { lat: addr.lat, lng: addr.lng, label: addr.label || '' }
        map.setView([addr.lat, addr.lng], 16)
        marker.setLatLng([addr.lat, addr.lng])
      }
    }
  } catch (e) {
    otpError.value = e.response?.data?.message || 'Kode salah.'
  } finally {
    verifyingOtp.value = false
  }
}

// Peta: default di sekitar Tangerang (lokasi asal toko). Klik peta atau geser pin
// buat pilih titik lokasi persis; ketik alamat juga otomatis geser peta ke situ.
const DEFAULT_CENTER = [-6.178, 106.631]
const mapEl = ref(null)
let map = null
let marker = null
const location = ref(null) // { lat, lng, label }
const geocoding = ref(false)
let geocodeTimer = null

const rateOptions = ref([])
const loadingRates = ref(false)
const selectedRate = ref(null)

const discountInput = ref('')
const discountError = ref('')
const applyingDiscount = ref(false)

const paying = ref(false)
const payError = ref('')

const total = computed(() => Math.max(0, cart.subtotal.value - cart.state.discountAmount) + (selectedRate.value?.price || 0))
const canPay = computed(() => form.value.customer_name && form.value.phone && form.value.detail && location.value && selectedRate.value)

onMounted(() => {
  delete L.Icon.Default.prototype._getIconUrl
  L.Icon.Default.mergeOptions({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow })

  axios.get(`${WILAYAH_API}/provinces.json`).then(({ data }) => { provinces.value = data })

  map = L.map(mapEl.value).setView(DEFAULT_CENTER, 11)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  marker = L.marker(DEFAULT_CENTER, { draggable: true }).addTo(map)
  marker.on('dragend', () => {
    const { lat, lng } = marker.getLatLng()
    setLocation(lat, lng, true)
  })
  map.on('click', (e) => {
    marker.setLatLng(e.latlng)
    setLocation(e.latlng.lat, e.latlng.lng, true)
  })
})

onBeforeUnmount(() => { map?.remove() })

// Geocoding (Nominatim) gratis, aman dipanggil otomatis. Cek ongkir (Biteship)
// BERBAYAR per panggilan — sengaja TIDAK auto-fetch di sini, nunggu klik tombol
// "Cek Ongkir" biar tidak boros saldo tiap buyer geser-geser pin.
async function setLocation(lat, lng, reverseGeocode) {
  location.value = { lat, lng, label: location.value?.label || '' }
  rateOptions.value = []
  selectedRate.value = null
  if (reverseGeocode) {
    try {
      const { data } = await axios.get('https://nominatim.openstreetmap.org/reverse', { params: { format: 'json', lat, lon: lng } })
      location.value = { lat, lng, label: data.display_name || '' }
    } catch {
      // biarkan tanpa label kalau reverse-geocode gagal, koordinat tetap terpakai
    }
  }
}

// Provinsi → Kota/Kabupaten → Kecamatan → Kelurahan, berurutan (data resmi BPS,
// gratis tanpa API key) — jauh lebih akurat daripada ketik bebas, karena nama yang
// dikirim ke Nominatim di langkah terakhir selalu nama administratif yang benar,
// bukan hasil ketikan yang bisa typo/beda ejaan.
const WILAYAH_API = 'https://www.emsifa.com/api-wilayah-indonesia/api'
const provinces = ref([])
const regencies = ref([])
const districts = ref([])
const villages = ref([])

// Teks yang diketik user (bisa dicari via <datalist> bawaan browser) — begitu teksnya
// cocok persis (case-insensitive) sama salah satu opsi, baru dianggap "terpilih".
const provinceQuery = ref('')
const regencyQuery = ref('')
const districtQuery = ref('')
const villageQuery = ref('')
const selectedProvince = ref(null)
const selectedRegency = ref(null)
const selectedDistrict = ref(null)
const selectedVillage = ref(null)
const selectedArea = ref(null) // { label, lat, lng } — hasil geocode kelurahan terpilih

function findByName(list, name) {
  const needle = name.trim().toLowerCase()
  return needle ? list.find((item) => item.name.toLowerCase() === needle) : null
}

async function onProvinceInput() {
  selectedRegency.value = null; selectedDistrict.value = null; selectedVillage.value = null
  regencyQuery.value = ''; districtQuery.value = ''; villageQuery.value = ''
  regencies.value = []; districts.value = []; villages.value = []

  selectedProvince.value = findByName(provinces.value, provinceQuery.value)
  if (!selectedProvince.value) return
  const { data } = await axios.get(`${WILAYAH_API}/regencies/${selectedProvince.value.id}.json`)
  regencies.value = data
}

async function onRegencyInput() {
  selectedDistrict.value = null; selectedVillage.value = null
  districtQuery.value = ''; villageQuery.value = ''
  districts.value = []; villages.value = []

  selectedRegency.value = findByName(regencies.value, regencyQuery.value)
  if (!selectedRegency.value) return
  const { data } = await axios.get(`${WILAYAH_API}/districts/${selectedRegency.value.id}.json`)
  districts.value = data
}

async function onDistrictInput() {
  selectedVillage.value = null
  villageQuery.value = ''
  villages.value = []

  selectedDistrict.value = findByName(districts.value, districtQuery.value)
  if (!selectedDistrict.value) return
  const { data } = await axios.get(`${WILAYAH_API}/villages/${selectedDistrict.value.id}.json`)
  villages.value = data
}

async function onVillageInput() {
  selectedVillage.value = findByName(villages.value, villageQuery.value)
  if (!selectedVillage.value) return

  const label = `${selectedVillage.value.name}, ${selectedDistrict.value.name}, ${selectedRegency.value.name}`

  geocoding.value = true
  try {
    const { data } = await axios.get('https://nominatim.openstreetmap.org/search', {
      params: { format: 'json', q: `${label}, Indonesia`, countrycodes: 'id', limit: 1 },
    })
    if (data[0]) {
      const lat = parseFloat(data[0].lat)
      const lng = parseFloat(data[0].lon)
      selectedArea.value = { label, lat, lng }
      map.setView([lat, lng], 15)
      marker.setLatLng([lat, lng])
      location.value = { lat, lng, label }
      rateOptions.value = []
      selectedRate.value = null
    }
  } finally {
    geocoding.value = false
  }
}

function onAddressInput() {
  clearTimeout(geocodeTimer)
  if (form.value.detail.length < 5) return
  geocodeTimer = setTimeout(async () => {
    geocoding.value = true
    try {
      // Dipersempit ke kecamatan/kelurahan yang sudah dipilih (kalau ada) biar Nominatim
      // gak nyari se-Indonesia — jauh lebih akurat daripada tembak alamat lengkap doang.
      const context = selectedArea.value ? selectedArea.value.label : 'Indonesia'
      const { data } = await axios.get('https://nominatim.openstreetmap.org/search', {
        params: { format: 'json', q: `${form.value.detail}, ${context}`, countrycodes: 'id', limit: 1 },
      })
      if (data[0]) {
        const lat = parseFloat(data[0].lat)
        const lng = parseFloat(data[0].lon)
        map.setView([lat, lng], 17)
        marker.setLatLng([lat, lng])
        location.value = { lat, lng, label: data[0].display_name }
        rateOptions.value = []
        selectedRate.value = null
      }
      // Kalau alamat detailnya gak ketemu, pin dibiarkan di titik area yang sudah
      // dipilih tadi (fallback aman) — bukan hilang atau balik ke lokasi ngasal.
    } finally {
      geocoding.value = false
    }
  }, 800)
}

// Cek ongkir Biteship berbayar per panggilan — cache per titik lokasi (dibulatkan
// ~11m) biar balik ke lokasi yang sama tidak nge-charge ulang.
const rateCache = new Map()
const cacheKey = (lat, lng) => `${lat.toFixed(4)},${lng.toFixed(4)}`

async function fetchRates() {
  if (!location.value) return
  selectedRate.value = null

  const key = cacheKey(location.value.lat, location.value.lng)
  if (rateCache.has(key)) {
    rateOptions.value = rateCache.get(key)
    return
  }

  loadingRates.value = true
  try {
    const { data } = await axios.post('/checkout/ongkir', {
      lat: location.value.lat,
      lng: location.value.lng,
      items: cart.state.items.map((i) => ({ product_id: i.id, qty: i.qty })),
    })
    rateOptions.value = data.options || []
    rateCache.set(key, rateOptions.value)
  } catch {
    rateOptions.value = []
  } finally {
    loadingRates.value = false
  }
}

async function applyDiscount() {
  if (!discountInput.value) return
  applyingDiscount.value = true
  discountError.value = ''
  try {
    const { data } = await axios.post('/checkout/diskon', { code: discountInput.value, subtotal: cart.subtotal.value })
    cart.setDiscount(data.code, data.amount)
  } catch (e) {
    discountError.value = e.response?.data?.message || 'Kode diskon tidak valid.'
    cart.setDiscount(null, 0)
  } finally {
    applyingDiscount.value = false
  }
}

async function pay() {
  if (!canPay.value) return
  payError.value = ''
  paying.value = true
  try {
    const { data } = await axios.post('/checkout', {
      customer_name: form.value.customer_name,
      phone: form.value.phone,
      email: form.value.email || null,
      shipping_address: {
        detail: form.value.detail,
        lat: location.value.lat,
        lng: location.value.lng,
        label: location.value.label,
      },
      shipping_option: {
        courier_code: selectedRate.value.courier_code,
        courier_service: selectedRate.value.courier_service,
        label: selectedRate.value.label,
      },
      shipping_cost: selectedRate.value.price,
      discount_code: cart.state.discountCode,
      payment_method: paymentMethod.value,
      save_profile: phoneVerified.value && saveProfile.value,
      items: cart.state.items.map((i) => ({ product_id: i.id, variant: i.variant, size: i.size, qty: i.qty })),
    })

    cart.clear()
    router.visit(data.tracking_url)
  } catch (e) {
    payError.value = e.response?.data?.message || 'Gagal membuat pesanan.'
  } finally {
    paying.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="cart" />

    <main>
      <section class="container-nale grid grid-cols-1 items-start gap-8 pb-16 pt-8 md:grid-cols-[1.5fr_1fr] md:gap-10 md:pb-[90px] md:pt-14">
        <div>
          <span class="eyebrow">Checkout</span>
          <h1 class="h-display mt-3 text-[26px] md:text-[38px]">Data Pengiriman</h1>

          <div class="mt-6 flex flex-col gap-3.5">
            <label class="flex flex-col gap-1.5">
              <span class="text-[12.5px] text-muted">No. WhatsApp</span>
              <div class="flex gap-2">
                <input v-model="form.phone" :disabled="phoneVerified" class="flex-1 rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" placeholder="08..." />
                <button v-if="!phoneVerified && !otpSent" class="btn-ghost whitespace-nowrap px-4 text-[12.5px]" :disabled="!form.phone || sendingOtp" @click="sendOtp">
                  {{ sendingOtp ? 'Mengirim...' : 'Verifikasi' }}
                </button>
                <span v-if="phoneVerified" class="flex items-center whitespace-nowrap text-[12.5px]" style="color:#5E7A4E">✓ Terverifikasi</span>
              </div>
              <span class="text-[11.5px] text-faint">Opsional — verifikasi buat auto-isi data dari order sebelumnya, atau simpan data sekarang. Tanpa verifikasi tetap bisa checkout biasa.</span>
            </label>

            <div v-if="otpSent && !phoneVerified" class="flex gap-2">
              <input v-model="otpCode" maxlength="6" class="w-32 rounded-lg2 border px-3.5 py-2.5 text-[14px] tracking-[0.2em]" style="border-color:#DDD5C9;background:#fff" placeholder="Kode 6 digit" />
              <button class="btn-primary px-4 text-[12.5px]" :disabled="verifyingOtp" @click="verifyOtp">{{ verifyingOtp ? 'Memeriksa...' : 'Cek Kode' }}</button>
              <button class="text-[12px] text-muted underline" :disabled="sendingOtp" @click="sendOtp">Kirim ulang</button>
            </div>
            <p v-if="otpError" class="text-[12px]" style="color:#B5675F">{{ otpError }}</p>
            <div v-if="welcomeBack" class="rounded-lg2 border px-3.5 py-2.5 text-[13px]" style="border-color:#D8E0CF;background:#F3F6EC;color:#5E7A4E">
              🎉 Selamat datang kembali! Makasih udah belanja lagi di NALE.
            </div>

            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Nama Penerima</span>
              <input v-model="form.customer_name" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Email (opsional)</span>
              <input v-model="form.email" type="email" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
            <div>
              <span class="text-[12.5px] text-muted">Provinsi, Kota, Kecamatan, Kelurahan</span>
              <div class="mt-1.5 grid grid-cols-2 gap-2.5">
                <input v-model="provinceQuery" @input="onProvinceInput" list="dl-provinces" placeholder="Cari provinsi..." class="rounded-lg2 border px-3 py-2.5 text-[13px]" style="border-color:#DDD5C9;background:#fff" />
                <datalist id="dl-provinces"><option v-for="p in provinces" :key="p.id" :value="p.name" /></datalist>

                <input v-model="regencyQuery" @input="onRegencyInput" list="dl-regencies" :disabled="!selectedProvince" placeholder="Cari kota/kabupaten..." class="rounded-lg2 border px-3 py-2.5 text-[13px]" style="border-color:#DDD5C9;background:#fff" />
                <datalist id="dl-regencies"><option v-for="r in regencies" :key="r.id" :value="r.name" /></datalist>

                <input v-model="districtQuery" @input="onDistrictInput" list="dl-districts" :disabled="!selectedRegency" placeholder="Cari kecamatan..." class="rounded-lg2 border px-3 py-2.5 text-[13px]" style="border-color:#DDD5C9;background:#fff" />
                <datalist id="dl-districts"><option v-for="d in districts" :key="d.id" :value="d.name" /></datalist>

                <input v-model="villageQuery" @input="onVillageInput" list="dl-villages" :disabled="!selectedDistrict" placeholder="Cari kelurahan/desa..." class="rounded-lg2 border px-3 py-2.5 text-[13px]" style="border-color:#DDD5C9;background:#fff" />
                <datalist id="dl-villages"><option v-for="v in villages" :key="v.id" :value="v.name" /></datalist>
              </div>
            </div>

            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Alamat Lengkap</span>
              <textarea v-model="form.detail" @input="onAddressInput" rows="2" class="resize-y rounded-lg2 border px-3.5 py-2.5 text-[14px] leading-[1.5]" style="border-color:#DDD5C9;background:#fff" placeholder="Nama jalan, nomor rumah, RT/RW, patokan"></textarea></label>

            <label v-if="phoneVerified" class="flex cursor-pointer items-center gap-2 text-[13px] text-muted">
              <input type="checkbox" v-model="saveProfile" />
              Simpan data saya untuk order berikutnya
            </label>

            <div>
              <div class="mb-2 flex items-center justify-between text-[12px] uppercase tracking-[0.14em] text-faint">
                <span>Titik Lokasi</span>
                <span v-if="geocoding" class="normal-case tracking-normal text-faint">Mencari lokasi...</span>
              </div>
              <div ref="mapEl" class="overflow-hidden rounded-lg2 border" style="height:260px;border-color:#DDD5C9"></div>
              <p class="mt-2 text-[11.5px] text-faint">Pilih provinsi/kota/kecamatan/kelurahan dulu di atas, lalu ketik alamat lengkap biar makin presisi — atau klik/geser pin langsung kapan aja.</p>
              <p v-if="location?.label" class="mt-1.5 text-[12px] text-ink">📍 {{ location.label }}</p>
            </div>

            <div v-if="location">
              <div class="mb-2.5 flex items-center justify-between">
                <span class="text-[12px] uppercase tracking-[0.14em] text-faint">Pilih Pengiriman</span>
                <button v-if="rateOptions.length === 0" class="btn-ghost px-4 py-1.5 text-[12.5px]" :disabled="loadingRates" @click="fetchRates">
                  {{ loadingRates ? 'Mengecek...' : 'Cek Ongkir' }}
                </button>
              </div>
              <p v-if="loadingRates" class="text-[13px] text-faint">Mengecek ongkir...</p>
              <p v-else-if="rateOptions.length === 0" class="text-[13px] text-faint">Klik "Cek Ongkir" buat lihat pilihan kurir ke lokasi ini.</p>
              <div v-else class="flex flex-col gap-2">
                <label v-for="opt in rateOptions" :key="opt.courier_code + opt.courier_service"
                  class="flex cursor-pointer items-center justify-between rounded-lg2 border px-3.5 py-2.5 text-[13px]"
                  :class="selectedRate === opt ? 'border-ink' : 'border-[#E2DBCF]'">
                  <span class="flex items-center gap-2.5">
                    <input type="radio" :checked="selectedRate === opt" @change="selectedRate = opt" />
                    <span>{{ opt.label }} <span class="text-faint">· {{ opt.duration }}</span></span>
                  </span>
                  <span class="whitespace-nowrap">{{ rp(opt.price) }}</span>
                </label>
              </div>
            </div>

            <div>
              <div class="mb-2.5 text-[12px] uppercase tracking-[0.14em] text-faint">Metode Pembayaran</div>
              <div class="flex flex-col gap-2">
                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg2 border px-3.5 py-2.5 text-[13px]" :class="paymentMethod === 'bank_transfer' ? 'border-ink' : 'border-[#E2DBCF]'">
                  <input type="radio" value="bank_transfer" v-model="paymentMethod" />
                  <span>Transfer Bank</span>
                </label>
                <label v-if="qrisAvailable" class="flex cursor-pointer items-center gap-2.5 rounded-lg2 border px-3.5 py-2.5 text-[13px]" :class="paymentMethod === 'qris' ? 'border-ink' : 'border-[#E2DBCF]'">
                  <input type="radio" value="qris" v-model="paymentMethod" />
                  <span>QRIS</span>
                </label>
              </div>
              <p class="mt-2 text-[11.5px] text-faint">Detail rekening/QRIS ditampilkan di halaman berikutnya setelah pesanan dibuat.</p>
            </div>
          </div>
        </div>

        <div class="rounded-lg2 border border-line bg-white p-5 shadow-lg shadow-black/[0.03] md:p-[26px] md:sticky md:top-24">
          <div class="mb-4 font-display text-[18px] text-ink md:mb-[18px] md:text-[20px]">Ringkasan</div>

          <div class="flex flex-col gap-2.5 border-b border-line pb-4">
            <div v-for="it in cart.state.items" :key="it.id + it.variant + it.size" class="flex items-center justify-between gap-2.5 text-[13px] text-muted">
              <div class="flex min-w-0 items-center gap-2.5">
                <img :src="it.img" :alt="it.name" class="h-10 w-10 flex-shrink-0 rounded-card bg-cardbg object-cover" />
                <span class="truncate">{{ it.name }} ({{ it.variant }}/{{ it.size }}) ×{{ it.qty }}</span>
              </div>
              <span class="flex-shrink-0 whitespace-nowrap text-ink">{{ rp(it.price * it.qty) }}</span>
            </div>
          </div>

          <div class="mt-3 flex gap-2">
            <input v-model="discountInput" placeholder="Kode diskon" class="flex-1 rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9;background:#fff" />
            <button class="btn-ghost px-4 py-2 text-[13px]" :disabled="applyingDiscount" @click="applyDiscount">Pakai</button>
          </div>
          <p v-if="discountError" class="mt-1.5 text-[12px]" style="color:#B5675F">{{ discountError }}</p>

          <div class="mt-3 flex flex-col gap-1.5 text-[13.5px] text-muted">
            <div class="flex justify-between"><span>Subtotal</span><span class="text-ink">{{ rp(cart.subtotal.value) }}</span></div>
            <div v-if="cart.state.discountAmount" class="flex justify-between"><span>Diskon</span><span class="text-ink">−{{ rp(cart.state.discountAmount) }}</span></div>
            <div class="flex justify-between"><span>Ongkir</span><span class="text-ink">{{ selectedRate ? rp(selectedRate.price) : '—' }}</span></div>
          </div>
          <div class="mt-3 flex justify-between border-t border-line pt-4 text-[16px] text-ink md:text-[17px]"><span class="font-display">Total</span><span class="whitespace-nowrap font-display">{{ rp(total) }}</span></div>

          <button class="btn-primary mt-5 w-full" :class="{ 'pointer-events-none opacity-40': !canPay || paying }" @click="pay">{{ paying ? 'Memproses...' : 'Buat Pesanan' }}</button>
          <p v-if="payError" class="mt-2 text-[12.5px]" style="color:#B5675F">{{ payError }}</p>
          <div class="mt-4 flex items-center justify-center gap-4 border-t border-line pt-4 text-[11px] text-faint">
            <span class="flex items-center gap-1.5"><Icon name="shield" :size="14" />Checkout Aman</span>
            <span class="flex items-center gap-1.5"><Icon name="truck" :size="14" />Dilacak Real-time</span>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
