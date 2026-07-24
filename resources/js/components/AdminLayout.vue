<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({ active: { type: String, default: 'products' } })

const nav = [
  { key: 'products', label: 'Produk', href: '/admin' },
  { key: 'orders', label: 'Pesanan', href: '/admin/pesanan' },
]

function logout() {
  router.post('/admin/logout')
}
</script>

<template>
  <div class="min-h-screen bg-panel font-sans text-ink" style="display:grid;grid-template-columns:248px 1fr">
    <aside class="sticky top-0 flex h-screen flex-col bg-ink px-[18px] py-[26px] text-panel">
      <div class="flex flex-col gap-px px-2.5 pb-1.5">
        <span class="font-display text-[24px] tracking-[0.16em]">NALE</span>
        <span class="text-[9px] uppercase tracking-[0.28em]" style="color:#8C8378">Admin Panel</span>
      </div>
      <nav class="mt-[26px] flex flex-col gap-[3px]">
        <Link v-for="n in nav" :key="n.key" :href="n.href"
          class="flex items-center gap-[11px] rounded-lg2 px-3 py-[11px] text-[14px]"
          :style="active === n.key ? 'background:#2C2822;color:#FBFAF8' : 'color:#B4ADA1'">
          <span class="h-[7px] w-[7px] rounded-pill" :style="`background:${active === n.key ? 'var(--accent)' : '#4A453D'}`"></span>
          {{ n.label }}
        </Link>
      </nav>
      <div class="mt-auto flex items-center gap-[11px] border-t pt-4 pl-2.5" style="border-color:#322E29">
        <div class="flex h-[34px] w-[34px] items-center justify-center rounded-pill text-[14px] font-semibold text-white" style="background:var(--accent)">A</div>
        <div class="flex flex-1 flex-col">
          <span class="text-[13px]">Admin NALE</span>
          <button class="text-left text-[11px]" style="color:#8C8378" @click="logout">Keluar →</button>
        </div>
      </div>
    </aside>

    <div class="flex min-w-0 flex-col">
      <slot />
    </div>
  </div>
</template>
