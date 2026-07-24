<script setup>
import AdminLayout from '@/components/AdminLayout.vue'
import { rp } from '@/stores/cart'

defineProps({ orders: { type: Array, default: () => [] } })

const STATUS = {
  pending: ['#F3E9CF', '#8A6E2E', 'Baru'],
  diproses: ['#D6E4EC', '#3E6479', 'Diproses'],
  dikirim: ['#DCE7CF', '#5E7A4E', 'Dikirim'],
  selesai: ['#EAE0F0', '#7A5B90', 'Selesai'],
}
function badge(s) { return STATUS[s] || ['#EEE', '#666', s] }
function fmt(d) { return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }
</script>

<template>
  <AdminLayout active="orders">
    <header class="sticky top-0 z-20 flex items-center justify-between border-b px-[34px] py-5" style="background:rgba(243,239,232,.85);backdrop-filter:blur(12px);border-color:#E2DBCF">
      <div>
        <h1 class="m-0 font-display text-[28px] font-light leading-none">Pesanan</h1>
        <span class="text-[13px] text-faint">{{ orders.length }} pesanan dari checkout website</span>
      </div>
    </header>

    <div class="px-[34px] pb-[60px] pt-[30px]">
      <div v-if="orders.length === 0" class="rounded-[12px] border bg-canvas px-[30px] py-[60px] text-center" style="border-color:#E9E3D9">
        <div class="font-display text-[22px] italic text-muted">Belum ada pesanan</div>
        <p class="mt-2 text-[14px] text-faint">Pesanan dari checkout website akan muncul di sini. Order via Shopee/Tokopedia tetap dikelola di marketplace masing-masing.</p>
      </div>

      <div v-else class="overflow-hidden rounded-[12px] border bg-canvas" style="border-color:#E9E3D9">
        <div class="grid gap-4 bg-panel px-[22px] py-3.5 text-[11.5px] uppercase tracking-[0.06em] text-faint" style="grid-template-columns:.7fr 1.4fr 1fr .8fr 1fr 1fr">
          <span>Order</span><span>Pelanggan</span><span>Tanggal</span><span>Item</span><span>Total</span><span>Status</span>
        </div>
        <div v-for="o in orders" :key="o.id" class="grid items-center gap-4 border-t px-[22px] py-3.5" style="grid-template-columns:.7fr 1.4fr 1fr .8fr 1fr 1fr;border-color:#F0EBE2">
          <span class="text-[13.5px] font-medium">#{{ o.id }}</span>
          <div class="min-w-0">
            <div class="truncate text-[13.5px]">{{ o.customer_name }}</div>
            <div class="truncate text-[12px] text-faint">{{ o.phone }}</div>
          </div>
          <span class="text-[13.5px] text-muted">{{ fmt(o.created_at) }}</span>
          <span class="text-[13.5px] text-muted">{{ o.items.length }} item</span>
          <span class="text-[13.5px]">{{ rp(o.total) }}</span>
          <div>
            <span class="rounded-pill px-3 py-[5px] text-[11.5px]" :style="`background:${badge(o.status)[0]};color:${badge(o.status)[1]}`">{{ badge(o.status)[2] }}</span>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
