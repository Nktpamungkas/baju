<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/components/AdminLayout.vue'

const props = defineProps({ reviews: { type: Array, required: true } })

const filter = ref('pending') // pending | approved | all
const filtered = computed(() => {
  if (filter.value === 'pending') return props.reviews.filter((r) => !r.approved)
  if (filter.value === 'approved') return props.reviews.filter((r) => r.approved)
  return props.reviews
})

function approve(r) {
  router.patch(`/admin/ulasan/${r.id}/approve`, {}, { preserveScroll: true })
}
function toggleFeatured(r) {
  router.patch(`/admin/ulasan/${r.id}/tampilkan`, {}, { preserveScroll: true })
}
function destroy(r) {
  if (confirm(`Hapus ulasan dari "${r.customer_name}"?`)) {
    router.delete(`/admin/ulasan/${r.id}`, { preserveScroll: true })
  }
}
function fmt(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
</script>

<template>
  <AdminLayout active="reviews">
    <header class="sticky top-0 z-20 flex flex-col gap-3 border-b px-4 py-4 md:flex-row md:items-center md:justify-between md:px-[34px] md:py-5" style="background:rgba(243,239,232,.85);backdrop-filter:blur(12px);border-color:#E2DBCF">
      <div>
        <h1 class="m-0 font-display text-[22px] font-light leading-none md:text-[28px]">Ulasan</h1>
        <span class="text-[12.5px] text-faint md:text-[13px]">{{ reviews.length }} ulasan masuk</span>
      </div>
      <div class="flex gap-2">
        <button
          v-for="f in [['pending', 'Perlu Direview'], ['approved', 'Disetujui'], ['all', 'Semua']]" :key="f[0]"
          class="rounded-pill border px-3.5 py-[7px] text-[12.5px]"
          :class="filter === f[0] ? 'border-ink bg-ink text-panel' : 'text-muted'" style="border-color:#D9D2C7"
          @click="filter = f[0]">{{ f[1] }}</button>
      </div>
    </header>

    <div class="flex flex-col gap-3 px-4 pb-10 pt-5 md:px-[34px] md:pb-[60px] md:pt-[30px]">
      <p v-if="filtered.length === 0" class="rounded-[12px] border bg-canvas p-8 text-center text-[13.5px] text-faint" style="border-color:#E9E3D9">
        Tidak ada ulasan di kategori ini.
      </p>

      <div v-for="r in filtered" :key="r.id" class="rounded-[12px] border bg-canvas p-4 md:p-5" style="border-color:#E9E3D9">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-[14px] text-ink">{{ r.customer_name }}</span>
              <span class="text-[12px] text-accent">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</span>
            </div>
            <div class="text-[12px] text-faint">Pesanan #{{ r.order_id }} · {{ fmt(r.created_at) }}</div>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <span v-if="!r.approved" class="rounded-pill px-2.5 py-1 text-[11px]" style="background:#F3E9CF;color:#8A6E2E">Menunggu Review</span>
            <span v-if="r.approved" class="rounded-pill px-2.5 py-1 text-[11px]" style="background:#DCE7CF;color:#5E7A4E">Disetujui</span>
            <span v-if="r.featured" class="rounded-pill px-2.5 py-1 text-[11px]" style="background:#E7D3C8;color:#8A5236">Tampil di Beranda</span>
          </div>
        </div>

        <p class="mt-3 text-[13.5px] leading-[1.6] text-muted">{{ r.comment }}</p>
        <a v-if="r.photo" :href="r.photo" target="_blank" rel="noopener" class="mt-2 inline-block text-[12.5px] text-muted underline">Lihat foto</a>

        <div class="mt-3 flex flex-wrap gap-2 border-t pt-3" style="border-color:#F0EBE2">
          <button v-if="!r.approved" class="rounded-pill bg-ink px-3.5 py-[7px] text-[12.5px] text-panel" @click="approve(r)">Setujui</button>
          <button v-if="r.approved" class="rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7" @click="toggleFeatured(r)">
            {{ r.featured ? 'Sembunyikan dari Beranda' : 'Tampilkan di Beranda' }}
          </button>
          <button class="rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#E7CFC7;color:#B5675F" @click="destroy(r)">Hapus</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
