<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps({ active: { type: String, default: 'products' } })

const nav = [
  { key: 'products', label: 'Produk', href: '/admin' },
]

function logout() {
  router.post('/admin/logout')
}
</script>

<template>
  <div class="min-h-screen bg-panel font-sans text-ink grid grid-cols-1 md:grid-cols-[248px_1fr]">
    <aside class="flex items-center justify-between gap-3 bg-ink px-4 py-3 text-panel md:sticky md:top-0 md:h-screen md:flex-col md:items-stretch md:justify-start md:px-[18px] md:py-[26px]">
      <div class="flex flex-col gap-px md:px-2.5 md:pb-1.5">
        <span class="font-display text-[18px] tracking-[0.16em] md:text-[24px]">NALE</span>
        <span class="hidden text-[9px] uppercase tracking-[0.28em] md:block" style="color:#8C8378">Admin Panel</span>
      </div>
      <nav class="flex gap-2 md:mt-[26px] md:flex-col md:gap-[3px]">
        <Link v-for="n in nav" :key="n.key" :href="n.href"
          class="flex items-center gap-[11px] rounded-lg2 px-3 py-2 text-[13px] md:py-[11px] md:text-[14px]"
          :style="active === n.key ? 'background:#2C2822;color:#FBFAF8' : 'color:#B4ADA1'">
          <span class="hidden h-[7px] w-[7px] rounded-pill md:inline-block" :style="`background:${active === n.key ? 'var(--accent)' : '#4A453D'}`"></span>
          {{ n.label }}
        </Link>
      </nav>
      <button class="text-[12px] md:hidden" style="color:#8C8378" @click="logout">Keluar →</button>
      <div class="mt-auto hidden items-center gap-[11px] border-t pt-4 pl-2.5 md:flex" style="border-color:#322E29">
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
