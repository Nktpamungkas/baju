<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import { rp } from '@/lib/format'

const phone = ref('')
const otpSent = ref(false)
const otpCode = ref('')
const sendingOtp = ref(false)
const verifyingOtp = ref(false)
const otpError = ref('')
const verified = ref(false)
const orders = ref([])
const hasDeliveredOrder = ref(false)

async function sendOtp() {
  if (!phone.value) return
  sendingOtp.value = true
  otpError.value = ''
  try {
    await axios.post('/checkout/kirim-otp', { phone: phone.value })
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
    const { data } = await axios.post('/checkout/verifikasi-otp', { phone: phone.value, code: otpCode.value })
    verified.value = true
    orders.value = data.orders || []
    hasDeliveredOrder.value = data.has_delivered_order
  } catch (e) {
    otpError.value = e.response?.data?.message || 'Kode salah.'
  } finally {
    verifyingOtp.value = false
  }
}

const PAYMENT_LABEL = { pending: 'Menunggu Pembayaran', menunggu_konfirmasi: 'Menunggu Konfirmasi', settlement: 'Sudah Dibayar', capture: 'Sudah Dibayar', deny: 'Ditolak', cancel: 'Dibatalkan', expire: 'Kedaluwarsa' }
const FULFILLMENT_LABEL = { unbooked: 'Menunggu Diproses', booked: 'Dijadwalkan Pickup', confirmed: 'Dikonfirmasi Kurir', allocated: 'Kurir Ditugaskan', picking_up: 'Kurir Menuju Lokasi', picked: 'Diambil Kurir', dropping_off: 'Dalam Pengiriman', delivered: 'Sudah Sampai', cancelled: 'Dibatalkan' }
function fmt(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="lookup" />

    <main>
      <section class="container-nale pb-16 pt-8 md:pb-[90px] md:pt-14">
        <span class="eyebrow">Cek Pesanan</span>
        <h1 class="h-display mt-3 text-[26px] md:text-[38px]">Lihat Status Pesanan Kamu</h1>
        <p class="mt-3 max-w-[480px] text-[14px] leading-[1.6] text-muted">Kehilangan link pesananmu? Masukin nomor WhatsApp yang kamu pakai waktu checkout, kami kirim kode verifikasi buat mastiin itu beneran kamu.</p>

        <div v-if="!verified" class="mt-6 max-w-[380px]">
          <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">No. WhatsApp</span>
            <input v-model="phone" :disabled="otpSent" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" placeholder="08..." /></label>

          <button v-if="!otpSent" class="btn-primary mt-3 w-full" :class="{ 'pointer-events-none opacity-40': !phone || sendingOtp }" @click="sendOtp">
            {{ sendingOtp ? 'Mengirim...' : 'Kirim Kode' }}
          </button>

          <div v-else class="mt-3 flex flex-col gap-2">
            <input v-model="otpCode" maxlength="6" class="rounded-lg2 border px-3.5 py-2.5 text-[14px] tracking-[0.2em]" style="border-color:#DDD5C9;background:#fff" placeholder="Kode 6 digit" />
            <button class="btn-primary w-full" :class="{ 'pointer-events-none opacity-40': verifyingOtp }" @click="verifyOtp">
              {{ verifyingOtp ? 'Memeriksa...' : 'Verifikasi' }}
            </button>
            <button class="text-[12px] text-muted underline" :disabled="sendingOtp" @click="sendOtp">Kirim ulang kode</button>
          </div>
          <p v-if="otpError" class="mt-2 text-[12.5px]" style="color:#B5675F">{{ otpError }}</p>
        </div>

        <div v-else class="mt-6 max-w-[560px]">
          <div v-if="hasDeliveredOrder" class="mb-5 rounded-lg2 border px-4 py-3 text-[13.5px]" style="border-color:#D8E0CF;background:#F3F6EC;color:#5E7A4E">
            🎉 Selamat datang kembali! Makasih udah belanja lagi di NALE.
          </div>

          <div v-if="orders.length === 0" class="rounded-lg2 border border-line bg-white px-6 py-10 text-center text-[14px] text-faint">
            Belum ada pesanan dengan nomor ini.
          </div>
          <div v-else class="flex flex-col gap-2.5">
            <button v-for="o in orders" :key="o.id" class="flex items-center justify-between rounded-lg2 border border-line bg-white px-4 py-3 text-left" @click="router.visit(`/pesanan/${o.tracking_token}`)">
              <div>
                <div class="text-[13.5px]">Pesanan #{{ o.id }} · {{ fmt(o.created_at) }}</div>
                <div class="mt-0.5 text-[12px] text-faint">{{ PAYMENT_LABEL[o.payment_status] || o.payment_status }} · {{ FULFILLMENT_LABEL[o.fulfillment_status] || o.fulfillment_status }}</div>
              </div>
              <div class="whitespace-nowrap text-[14px] text-ink">{{ rp(o.total) }}</div>
            </button>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
