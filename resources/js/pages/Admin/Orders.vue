<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/components/AdminLayout.vue'
import { rp } from '@/lib/format'

const props = defineProps({
  orders: { type: Array, required: true },
  discounts: { type: Array, required: true },
})

const page = usePage()

const search = ref('')
const filteredOrders = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return props.orders
  return props.orders.filter((o) => String(o.id).includes(q) || o.customer_name.toLowerCase().includes(q) || o.phone.includes(q))
})

const stats = computed(() => ({
  total: props.orders.length,
  menungguKonfirmasi: props.orders.filter((o) => o.payment_status === 'menunggu_konfirmasi').length,
  perluDiproses: props.orders.filter((o) => o.payment_status === 'settlement' && o.fulfillment_status === 'unbooked').length,
  omzet: props.orders.filter((o) => o.payment_status === 'settlement').reduce((sum, o) => sum + o.total, 0),
}))

const expanded = ref(null)
function toggle(id) { expanded.value = expanded.value === id ? null : id }

function arrangeShipment(order) {
  router.patch(`/admin/pesanan/${order.id}/atur-pengiriman`, {}, { preserveScroll: true })
}

// Riwayat tracking dimuat lazy per pesanan (bukan sekaligus di index()) biar tidak
// nembak API Biteship berkali-kali cuma buat nampilin daftar pesanan.
const tracking = ref({})
const loadingTracking = ref({})
async function fetchTracking(order) {
  loadingTracking.value = { ...loadingTracking.value, [order.id]: true }
  try {
    const { data } = await axios.get(`/admin/pesanan/${order.id}/tracking`)
    tracking.value = { ...tracking.value, [order.id]: data.carrier }
  } finally {
    loadingTracking.value = { ...loadingTracking.value, [order.id]: false }
  }
}

function confirmPayment(order) {
  if (confirm(`Tandai pesanan #${order.id} sudah lunas?`)) {
    router.patch(`/admin/pesanan/${order.id}/konfirmasi-bayar`, {}, { preserveScroll: true })
  }
}

function cancelOrder(order) {
  if (confirm(`Batalkan pesanan #${order.id}? ${order.biteship_order_id ? 'Pengiriman yang sudah dibooking juga akan dibatalkan di Biteship.' : ''}`)) {
    router.patch(`/admin/pesanan/${order.id}/batalkan`, {}, { preserveScroll: true })
  }
}

// Edit data pembeli/alamat — cuma dibuka kalau belum dibooking ke kurir (lihat guard
// yang sama di OrderService::updateOrder).
const editingOrder = ref(null)
const orderDraft = ref(null)
function startEditOrder(o) {
  editingOrder.value = o.id
  orderDraft.value = {
    customer_name: o.customer_name,
    phone: o.phone,
    email: o.email || '',
    detail: o.shipping_address?.detail || '',
    label: o.shipping_address?.label || '',
    lat: o.shipping_address?.lat,
    lng: o.shipping_address?.lng,
  }
}
function cancelEditOrder() { editingOrder.value = null; orderDraft.value = null }
function saveOrder(o) {
  router.put(`/admin/pesanan/${o.id}`, {
    customer_name: orderDraft.value.customer_name,
    phone: orderDraft.value.phone,
    email: orderDraft.value.email || null,
    shipping_address: { detail: orderDraft.value.detail, label: orderDraft.value.label, lat: orderDraft.value.lat, lng: orderDraft.value.lng },
  }, { preserveScroll: true, onSuccess: cancelEditOrder })
}

function uploadQris(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('qris', file)
  router.post('/admin/qris', fd, { preserveScroll: true })
}

const newDiscount = ref({ code: '', type: 'percent', value: 10, min_subtotal: 0, expires_at: '', featured: false })
function addDiscount() {
  router.post('/admin/diskon', newDiscount.value, {
    preserveScroll: true,
    onSuccess: () => { newDiscount.value = { code: '', type: 'percent', value: 10, min_subtotal: 0, expires_at: '', featured: false } },
  })
}

const editingDiscount = ref(null)
const discountDraft = ref(null)
function startEditDiscount(d) {
  editingDiscount.value = d.id
  discountDraft.value = { code: d.code, type: d.type, value: d.value, min_subtotal: d.min_subtotal || 0, expires_at: d.expires_at ? d.expires_at.slice(0, 10) : '', featured: !!d.featured }
}
function saveDiscount(d) {
  router.put(`/admin/diskon/${d.id}`, discountDraft.value, { preserveScroll: true, onSuccess: () => { editingDiscount.value = null } })
}
function toggleDiscount(d) { router.patch(`/admin/diskon/${d.id}/toggle`, {}, { preserveScroll: true }) }
function deleteDiscount(d) {
  if (confirm(`Hapus kode "${d.code}"?`)) router.delete(`/admin/diskon/${d.id}`, { preserveScroll: true })
}

const PAYMENT_LABEL = { pending: 'Menunggu', menunggu_konfirmasi: 'Cek Bukti Bayar', settlement: 'Lunas', capture: 'Lunas', deny: 'Ditolak', cancel: 'Dibatalkan', expire: 'Kedaluwarsa' }
const FULFILLMENT_LABEL = { unbooked: 'Belum Diproses', booked: 'Pickup Terjadwal', confirmed: 'Dikonfirmasi', allocated: 'Kurir Ditugaskan', picking_up: 'Menuju Lokasi', picked: 'Diambil Kurir', dropping_off: 'Dikirim', delivered: 'Sampai', cancelled: 'Dibatalkan' }
function fmt(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
</script>

<template>
  <AdminLayout active="orders">
    <header class="sticky top-0 z-20 border-b px-4 py-4 md:px-[34px] md:py-5" style="background:rgba(243,239,232,.85);backdrop-filter:blur(12px);border-color:#E2DBCF">
      <h1 class="m-0 font-display text-[22px] font-light leading-none md:text-[28px]">Pesanan</h1>
      <span class="text-[12.5px] text-faint md:text-[13px]">{{ orders.length }} pesanan dari checkout website</span>
    </header>

    <div class="px-4 pb-10 pt-5 md:px-[34px] md:pb-[60px] md:pt-[30px]">
      <div v-if="page.props.errors?.pickup || page.props.errors?.cancel || page.props.errors?.edit" class="mb-4 rounded-lg2 border px-4 py-3 text-[13px]" style="border-color:#E7CFC7;background:#FBF2F0;color:#B5675F">
        {{ page.props.errors.pickup || page.props.errors.cancel || page.props.errors.edit }}
      </div>

      <div class="mb-5 grid grid-cols-2 gap-2.5 md:grid-cols-4 md:gap-3">
        <div class="rounded-lg2 border bg-canvas p-3.5" style="border-color:#E9E3D9">
          <div class="text-[11px] uppercase tracking-[0.06em] text-faint">Total Pesanan</div>
          <div class="mt-1 font-display text-[20px]">{{ stats.total }}</div>
        </div>
        <div class="rounded-lg2 border bg-canvas p-3.5" style="border-color:#E9E3D9">
          <div class="text-[11px] uppercase tracking-[0.06em] text-faint">Cek Bukti Bayar</div>
          <div class="mt-1 font-display text-[20px]" :style="stats.menungguKonfirmasi ? 'color:#B5675F' : ''">{{ stats.menungguKonfirmasi }}</div>
        </div>
        <div class="rounded-lg2 border bg-canvas p-3.5" style="border-color:#E9E3D9">
          <div class="text-[11px] uppercase tracking-[0.06em] text-faint">Perlu Diatur Kirim</div>
          <div class="mt-1 font-display text-[20px]" :style="stats.perluDiproses ? 'color:#B5675F' : ''">{{ stats.perluDiproses }}</div>
        </div>
        <div class="rounded-lg2 border bg-canvas p-3.5" style="border-color:#E9E3D9">
          <div class="text-[11px] uppercase tracking-[0.06em] text-faint">Omzet (Lunas)</div>
          <div class="mt-1 font-display text-[20px]">{{ rp(stats.omzet) }}</div>
        </div>
      </div>

      <input v-model="search" placeholder="Cari nama, no. HP, atau nomor pesanan..." class="mb-4 w-full max-w-[360px] rounded-lg2 border px-3.5 py-2.5 text-[13px]" style="border-color:#DDD5C9;background:#fff" />

      <div v-if="filteredOrders.length === 0" class="rounded-[12px] border bg-canvas px-6 py-14 text-center" style="border-color:#E9E3D9">
        <div class="font-display text-[19px] italic text-muted">{{ orders.length === 0 ? 'Belum ada pesanan' : 'Tidak ada pesanan yang cocok' }}</div>
      </div>

      <div v-else class="overflow-hidden rounded-[12px] border bg-canvas" style="border-color:#E9E3D9">
        <div v-for="o in filteredOrders" :key="o.id" class="border-t" style="border-color:#F0EBE2">
          <button class="flex w-full flex-col gap-2 p-4 text-left md:flex-row md:items-center md:justify-between md:gap-4 md:px-[22px] md:py-3.5" @click="toggle(o.id)">
            <div class="flex items-center gap-3">
              <span class="text-[13.5px] font-medium">#{{ o.id }}</span>
              <span class="text-[13px] text-muted">{{ o.customer_name }}</span>
              <span class="text-[12px] text-faint">{{ fmt(o.created_at) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="rounded-pill px-2.5 py-1 text-[11px]" :style="o.payment_status === 'settlement' ? 'background:#DCE7CF;color:#5E7A4E' : (o.payment_status === 'cancel' ? 'background:#F3DEDA;color:#B5675F' : 'background:#F3E9CF;color:#8A6E2E')">{{ PAYMENT_LABEL[o.payment_status] || o.payment_status }}</span>
              <span class="rounded-pill px-2.5 py-1 text-[11px]" style="background:#D6E4EC;color:#3E6479">{{ FULFILLMENT_LABEL[o.fulfillment_status] || o.fulfillment_status }}</span>
              <span class="whitespace-nowrap text-[13.5px]">{{ rp(o.total) }}</span>
            </div>
          </button>

          <div v-if="expanded === o.id" class="flex flex-col gap-3 border-t px-4 pb-4 pt-3 md:px-[22px]" style="border-color:#F0EBE2">
            <div v-for="it in o.items" :key="it.id" class="flex justify-between text-[13px] text-muted">
              <span>{{ it.name }} ({{ it.variant }}/{{ it.size }}) ×{{ it.qty }}</span>
              <span class="text-ink">{{ rp(it.price * it.qty) }}</span>
            </div>

            <template v-if="editingOrder === o.id">
              <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                <input v-model="orderDraft.customer_name" placeholder="Nama" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
                <input v-model="orderDraft.phone" placeholder="No. WhatsApp" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
                <input v-model="orderDraft.email" placeholder="Email (opsional)" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
                <input v-model="orderDraft.detail" placeholder="Alamat lengkap" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
              </div>
              <div class="flex gap-2">
                <button class="rounded-pill bg-ink px-3.5 py-[7px] text-[12.5px] text-panel" @click="saveOrder(o)">Simpan</button>
                <button class="rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7" @click="cancelEditOrder">Batal</button>
              </div>
            </template>
            <template v-else>
              <div class="text-[12.5px] text-faint">{{ o.phone }} · {{ o.shipping_address?.detail }}, {{ o.shipping_address?.label }}</div>
            </template>

            <div class="text-[12.5px] text-faint">Metode: {{ o.payment_method === 'qris' ? 'QRIS' : 'Transfer Bank' }}</div>
            <div v-if="o.waybill_id" class="text-[12.5px] text-ink">No. Resi: <b>{{ o.waybill_id }}</b></div>
            <a v-if="o.payment_proof" :href="o.payment_proof" target="_blank" rel="noopener" class="self-start text-[12.5px] text-muted underline">Lihat Bukti Pembayaran</a>
            <div class="flex flex-wrap gap-2">
              <button
                v-if="o.payment_status !== 'settlement'"
                class="self-start rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7"
                @click="confirmPayment(o)">Tandai Lunas</button>
              <button
                v-if="o.payment_status === 'settlement' && o.fulfillment_status === 'unbooked'"
                class="self-start rounded-pill bg-ink px-3.5 py-[7px] text-[12.5px] text-panel"
                @click="arrangeShipment(o)">Atur Pengiriman Sekarang</button>
              <button
                v-if="o.biteship_order_id"
                class="self-start rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7"
                :disabled="loadingTracking[o.id]" @click="fetchTracking(o)">
                {{ loadingTracking[o.id] ? 'Memuat...' : 'Cek Update Kurir' }}
              </button>
              <button
                v-if="o.fulfillment_status === 'unbooked' && editingOrder !== o.id"
                class="self-start rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7"
                @click="startEditOrder(o)">Edit Data</button>
              <button
                v-if="o.payment_status !== 'cancel' && o.payment_status !== 'expire' && o.fulfillment_status !== 'delivered'"
                class="self-start rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#E7CFC7;color:#B5675F"
                @click="cancelOrder(o)">Batalkan Pesanan</button>
            </div>

            <div v-if="tracking[o.id]?.history?.length" class="flex flex-col gap-1.5 rounded-lg2 border p-3" style="background:#F6F2EB;border-color:#EBE5DB">
              <div v-for="(h, i) in tracking[o.id].history" :key="i" class="text-[12px] text-muted">
                <span class="text-ink">{{ h.note || h.status }}</span>
                <span v-if="h.updated_at" class="text-faint"> · {{ new Date(h.updated_at).toLocaleString('id-ID') }}</span>
              </div>
            </div>
            <p v-else-if="tracking[o.id] === null" class="text-[12px] text-faint">Belum ada update dari kurir.</p>
          </div>
        </div>
      </div>

      <div class="mt-8 rounded-[12px] border bg-canvas p-5 md:p-[22px]" style="border-color:#E9E3D9">
        <h2 class="m-0 font-display text-[17px] font-light md:text-[19px]">Kode Diskon</h2>

        <div class="mt-4 flex flex-col gap-2">
          <div v-for="d in discounts" :key="d.id" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#EBE5DB">
            <div v-if="editingDiscount === d.id" class="flex flex-col gap-2">
              <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                <input v-model="discountDraft.code" class="rounded-lg2 border px-2.5 py-1.5 text-[12.5px] uppercase" style="border-color:#DDD5C9" />
                <select v-model="discountDraft.type" class="rounded-lg2 border px-2.5 py-1.5 text-[12.5px]" style="border-color:#DDD5C9">
                  <option value="percent">Persen</option>
                  <option value="fixed">Nominal</option>
                </select>
                <input v-model="discountDraft.value" type="number" class="rounded-lg2 border px-2.5 py-1.5 text-[12.5px]" style="border-color:#DDD5C9" />
                <input v-model="discountDraft.min_subtotal" type="number" class="rounded-lg2 border px-2.5 py-1.5 text-[12.5px]" style="border-color:#DDD5C9" />
              </div>
              <label class="flex items-center gap-1.5 text-[12.5px] text-muted">
                <input v-model="discountDraft.featured" type="checkbox" /> Tampilkan sebagai banner promo di beranda
              </label>
              <div class="flex gap-2">
                <button class="rounded-pill bg-ink px-3 py-1 text-[12px] text-panel" @click="saveDiscount(d)">Simpan</button>
                <button class="rounded-pill border px-3 py-1 text-[12px]" style="border-color:#D9D2C7" @click="editingDiscount = null">Batal</button>
              </div>
            </div>
            <div v-else class="flex items-center justify-between gap-2">
              <span :class="{ 'text-faint line-through': !d.active }">
                {{ d.code }} — {{ d.type === 'percent' ? `${d.value}%` : rp(d.value) }}
                <span v-if="d.featured" class="ml-1 text-[11px] text-accent">· di beranda</span>
              </span>
              <span class="flex gap-2">
                <button class="text-[12px] text-muted underline" @click="startEditDiscount(d)">Edit</button>
                <button class="text-[12px] text-muted underline" @click="toggleDiscount(d)">{{ d.active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                <button class="text-[12px] underline" style="color:#B5675F" @click="deleteDiscount(d)">Hapus</button>
              </span>
            </div>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2.5 md:grid-cols-5">
          <input v-model="newDiscount.code" placeholder="KODE" class="rounded-lg2 border px-3 py-2 text-[13px] uppercase" style="border-color:#DDD5C9" />
          <select v-model="newDiscount.type" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9">
            <option value="percent">Persen</option>
            <option value="fixed">Nominal</option>
          </select>
          <input v-model="newDiscount.value" type="number" placeholder="Nilai" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
          <input v-model="newDiscount.min_subtotal" type="number" placeholder="Min. belanja" class="rounded-lg2 border px-3 py-2 text-[13px]" style="border-color:#DDD5C9" />
          <button class="rounded-pill bg-ink px-3.5 py-2 text-[13px] font-medium text-panel" @click="addDiscount">+ Tambah</button>
        </div>
        <label class="mt-2.5 flex items-center gap-1.5 text-[12.5px] text-muted">
          <input v-model="newDiscount.featured" type="checkbox" /> Tampilkan sebagai banner promo di beranda
        </label>
      </div>

      <div class="mt-6 rounded-[12px] border bg-canvas p-5 md:p-[22px]" style="border-color:#E9E3D9">
        <h2 class="m-0 font-display text-[17px] font-light md:text-[19px]">QRIS Pembayaran</h2>
        <p class="mt-1.5 text-[12.5px] text-faint">Upload gambar QRIS kamu (dari bank/e-wallet) — ini yang bakal ditampilkan ke pembeli yang pilih bayar QRIS.</p>
        <label class="mt-3 inline-block cursor-pointer rounded-pill border px-4 py-2 text-[13px]" style="border-color:#D9D2C7">
          Pilih Gambar QRIS
          <input type="file" accept="image/*" class="hidden" @change="uploadQris" />
        </label>
      </div>
    </div>
  </AdminLayout>
</template>
