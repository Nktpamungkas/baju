<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppHeader from '@/components/AppHeader.vue'
import { rp } from '@/lib/format'
import { useWhatsapp } from '@/lib/whatsapp'

const props = defineProps({
  order: { type: Object, required: true },
  carrier: { type: Object, default: null },
  review: { type: Object, default: null },
})

const page = usePage()
const uploading = ref(false)
const refreshing = ref(false)
const fileInput = ref(null)

const reviewForm = ref({ rating: 5, comment: '' })
const reviewPhoto = ref(null)
const reviewPhotoInput = ref(null)
const submittingReview = ref(false)
const reviewError = ref('')

async function submitReview() {
  submittingReview.value = true
  reviewError.value = ''
  const fd = new FormData()
  fd.append('rating', reviewForm.value.rating)
  fd.append('comment', reviewForm.value.comment)
  if (reviewPhoto.value) fd.append('photo', reviewPhoto.value)
  try {
    await axios.post(`/pesanan/${props.order.tracking_token}/ulasan`, fd)
    router.reload()
  } catch (e) {
    reviewError.value = e.response?.data?.message || 'Gagal mengirim ulasan.'
  } finally {
    submittingReview.value = false
  }
}
function pickReviewPhoto() {
  reviewPhotoInput.value?.click()
}

const { href: waHref } = useWhatsapp(`Halo, saya mau tanya soal pesanan saya (#${props.order.id}).`, props.order.phone)

const PAYMENT_LABEL = {
  pending: 'Menunggu Pembayaran',
  menunggu_konfirmasi: 'Menunggu Konfirmasi Admin',
  settlement: 'Sudah Dibayar',
  capture: 'Sudah Dibayar',
  deny: 'Pembayaran Ditolak',
  cancel: 'Dibatalkan',
  expire: 'Kedaluwarsa',
}
const FULFILLMENT_LABEL = {
  unbooked: 'Menunggu Diproses',
  booked: 'Dijadwalkan Pickup',
  confirmed: 'Dikonfirmasi Kurir',
  allocated: 'Kurir Ditugaskan',
  picking_up: 'Kurir Menuju Lokasi',
  picked: 'Sudah Diambil Kurir',
  dropping_off: 'Dalam Pengiriman',
  delivered: 'Sudah Sampai',
  cancelled: 'Dibatalkan',
}

const paymentLabel = computed(() => PAYMENT_LABEL[props.order.payment_status] || props.order.payment_status)
const fulfillmentLabel = computed(() => FULFILLMENT_LABEL[props.order.fulfillment_status] || props.order.fulfillment_status)
const needsPayment = computed(() => props.order.payment_status === 'pending' || props.order.payment_status === 'menunggu_konfirmasi')
const isSettled = computed(() => props.order.payment_status === 'settlement')

const STAGES = ['Diproses', 'Dijemput Kurir', 'Dalam Pengiriman', 'Sudah Sampai']
const STAGE_INDEX = {
  unbooked: 0,
  booked: 1, confirmed: 1, allocated: 1, picking_up: 1,
  picked: 2, dropping_off: 2,
  delivered: 3,
}
const isCancelled = computed(() => props.order.fulfillment_status === 'cancelled')
const stageIndex = computed(() => STAGE_INDEX[props.order.fulfillment_status] ?? 0)

function pickFile() {
  fileInput.value?.click()
}

async function uploadProof(e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('photo', file)
  uploading.value = true
  try {
    await axios.post(`/pesanan/${props.order.tracking_token}/bukti-bayar`, fd)
    router.reload()
  } finally {
    uploading.value = false
  }
}

function refreshStatus() {
  refreshing.value = true
  router.reload({ onFinish: () => { refreshing.value = false } })
}
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="cart" />

    <main>
      <section class="container-nale grid grid-cols-1 gap-8 pb-16 pt-8 md:grid-cols-[1.5fr_1fr] md:gap-10 md:pb-[90px] md:pt-14">
        <div>
          <span class="eyebrow">Pesanan #{{ order.id }}</span>
          <h1 class="h-display mt-3 text-[26px] md:text-[38px]">Terima kasih, {{ order.customer_name }}!</h1>

          <div class="mt-6 flex flex-col gap-2.5">
            <div v-for="it in order.items" :key="it.id" class="flex justify-between border-b border-line pb-2.5 text-[13.5px] text-muted">
              <span>{{ it.name }} ({{ it.variant }}/{{ it.size }}) ×{{ it.qty }}</span>
              <span class="whitespace-nowrap text-ink">{{ rp(it.price * it.qty) }}</span>
            </div>
          </div>

          <!-- Instruksi pembayaran manual -->
          <div v-if="needsPayment" class="mt-6 rounded-lg2 border border-line bg-white p-5">
            <div class="mb-1 text-[12px] uppercase tracking-[0.14em] text-faint">Cara Bayar</div>

            <div v-if="order.payment_method === 'qris'" class="mt-3">
              <img src="/img/qris.png" alt="QRIS" class="mx-auto h-56 w-56 object-contain" />
              <p class="mt-2 text-center text-[13px] text-muted">Scan QRIS di atas pakai aplikasi bank/e-wallet apa saja, transfer tepat sejumlah <b>{{ rp(order.total) }}</b>.</p>
            </div>
            <div v-else class="mt-3 rounded-lg2 bg-panel px-4 py-3 text-[13.5px]">
              <div>Bank: <b>{{ page.props.bank?.name }}</b></div>
              <div>No. Rekening: <b>{{ page.props.bank?.account_number }}</b></div>
              <div>Atas Nama: <b>{{ page.props.bank?.account_name }}</b></div>
              <div class="mt-1.5">Transfer tepat sejumlah <b>{{ rp(order.total) }}</b>.</div>
            </div>

            <div v-if="order.payment_status === 'pending'" class="mt-4">
              <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="uploadProof" />
              <button class="btn-primary w-full" :class="{ 'pointer-events-none opacity-40': uploading }" @click="pickFile">
                {{ uploading ? 'Mengunggah...' : 'Upload Bukti Pembayaran' }}
              </button>
            </div>
            <p v-else class="mt-4 text-[13px] text-muted">Bukti pembayaran sudah kami terima, sedang dicek admin. Kamu bisa upload ulang kalau perlu ganti buktinya.</p>
            <button v-if="order.payment_status === 'menunggu_konfirmasi'" class="mt-2 text-[12.5px] text-muted underline" @click="pickFile">Upload ulang bukti</button>
          </div>

          <div class="mt-6 rounded-lg2 border border-line bg-white p-5">
            <div class="mb-1 text-[12px] uppercase tracking-[0.14em] text-faint">Status Pengiriman</div>

            <p v-if="isCancelled" class="text-[15px]" style="color:#B5675F">Pesanan Dibatalkan</p>
            <div v-else class="mt-3 flex items-center">
              <template v-for="(label, i) in STAGES" :key="label">
                <div class="flex flex-1 flex-col items-center text-center">
                  <div
                    class="flex h-6 w-6 items-center justify-center rounded-full text-[11px]"
                    :class="i <= stageIndex ? 'bg-ink text-canvas' : 'bg-panel text-faint'">
                    <span v-if="i < stageIndex || (i === stageIndex && order.fulfillment_status === 'delivered')">✓</span><span v-else>{{ i + 1 }}</span>
                  </div>
                  <span class="mt-1.5 text-[10.5px] leading-tight" :class="i <= stageIndex ? 'text-ink' : 'text-faint'">{{ label }}</span>
                </div>
                <div v-if="i < STAGES.length - 1" class="mb-4 h-px flex-1" :class="i < stageIndex ? 'bg-ink' : 'bg-line'"></div>
              </template>
            </div>

            <div class="mt-4 text-[15px]">{{ fulfillmentLabel }}</div>
            <div v-if="order.shipping_option" class="mt-1 text-[12.5px] text-faint">{{ order.shipping_option.label }}</div>
            <div v-if="order.waybill_id" class="mt-1 text-[12.5px] text-ink">No. Resi: <b>{{ order.waybill_id }}</b></div>

            <div v-if="carrier?.history?.length" class="mt-4 flex flex-col gap-2 border-t border-line pt-4">
              <div v-for="(h, i) in carrier.history" :key="i" class="text-[12.5px] text-muted">
                <span class="text-ink">{{ h.note || h.status }}</span>
                <span v-if="h.updated_at" class="text-faint"> · {{ new Date(h.updated_at).toLocaleString('id-ID') }}</span>
              </div>
            </div>

            <button class="mt-4 text-[12.5px] text-muted underline" :disabled="refreshing" @click="refreshStatus">
              {{ refreshing ? 'Memuat...' : 'Refresh status' }}
            </button>
          </div>

          <!-- Ulasan — cuma muncul kalau barang sudah sampai -->
          <div v-if="order.fulfillment_status === 'delivered'" class="mt-6 rounded-lg2 border border-line bg-white p-5">
            <div class="mb-1 text-[12px] uppercase tracking-[0.14em] text-faint">Ulasan</div>

            <div v-if="review">
              <div class="mt-2 text-[15px] text-accent">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</div>
              <p class="mt-2 text-[13.5px] leading-[1.6] text-muted">{{ review.comment }}</p>
              <p class="mt-2 text-[12.5px] text-faint">Terima kasih sudah kasih ulasan!</p>
            </div>
            <form v-else class="mt-3" @submit.prevent="submitReview">
              <p class="text-[13.5px] text-muted">Barangnya sudah sampai? Ceritain pengalaman belanjamu ya.</p>
              <div class="mt-3 flex gap-1 text-[22px] text-accent">
                <button v-for="n in 5" :key="n" type="button" @click="reviewForm.rating = n">{{ n <= reviewForm.rating ? '★' : '☆' }}</button>
              </div>
              <textarea
                v-model="reviewForm.comment" rows="3" required maxlength="1000" placeholder="Bagaimana kualitas barang dan pengirimannya?"
                class="mt-3 w-full rounded-lg2 border px-3.5 py-2.5 text-[13.5px]" style="border-color:#D9D2C7"></textarea>
              <div class="mt-2.5 flex items-center gap-3">
                <input ref="reviewPhotoInput" type="file" accept="image/*" class="hidden" @change="reviewPhoto = $event.target.files[0]" />
                <button type="button" class="text-[12.5px] text-muted underline" @click="pickReviewPhoto">
                  {{ reviewPhoto ? reviewPhoto.name : '+ Tambah foto (opsional)' }}
                </button>
              </div>
              <p v-if="reviewError" class="mt-2 text-[12.5px]" style="color:#B5675F">{{ reviewError }}</p>
              <button class="btn-primary mt-3.5 w-full" :class="{ 'pointer-events-none opacity-40': submittingReview }" type="submit">
                {{ submittingReview ? 'Mengirim...' : 'Kirim Ulasan' }}
              </button>
            </form>
          </div>

          <a v-if="waHref" :href="waHref" target="_blank" rel="noopener" class="mt-3 inline-block text-[13px] text-muted underline">Kirim link ini ke WhatsApp saya</a>
        </div>

        <div class="rounded-lg2 border border-line bg-white p-5 md:p-[26px]">
          <div class="mb-4 font-display text-[18px] text-ink md:text-[20px]">Ringkasan</div>
          <div class="flex flex-col gap-1.5 text-[13.5px] text-muted">
            <div class="flex justify-between"><span>Subtotal</span><span class="text-ink">{{ rp(order.subtotal) }}</span></div>
            <div v-if="order.discount_amount" class="flex justify-between"><span>Diskon</span><span class="text-ink">−{{ rp(order.discount_amount) }}</span></div>
            <div class="flex justify-between"><span>Ongkir</span><span class="text-ink">{{ rp(order.shipping_cost) }}</span></div>
          </div>
          <div class="mt-3 flex justify-between border-t border-line pt-4 text-[16px] text-ink md:text-[17px]"><span class="font-display">Total</span><span class="whitespace-nowrap font-display">{{ rp(order.total) }}</span></div>

          <div class="mt-4 rounded-lg2 border px-3.5 py-3 text-[13px]" :style="isSettled ? 'border-color:#D8E0CF;color:#5E7A4E' : 'border-color:#E7CFC7;color:#B5675F'">
            {{ paymentLabel }}
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
