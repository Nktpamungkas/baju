<script setup>
import { Link } from '@inertiajs/vue3'
import { useCart } from '@/stores/cart'
const cart = useCart()
defineProps({ active: { type: String, default: 'home' } }) // 'home' | 'catalog' | 'about'
</script>

<template>
  <header class="sticky top-0 z-50 flex items-center justify-between border-b border-line px-10 py-[18px] backdrop-blur-md" style="background:rgba(251,250,248,.82)">
    <Link href="/" class="flex flex-col items-start gap-px">
      <span class="font-display text-[26px] leading-none tracking-[0.16em] text-ink">NALE</span>
      <span class="ml-0.5 text-[9px] uppercase tracking-[0.32em] text-faint">Baju Anak</span>
    </Link>

    <nav class="flex items-center gap-[34px]">
      <Link href="/" class="text-[14px]" :class="active === 'home' ? 'text-ink' : 'text-faint'">Beranda</Link>
      <Link href="/katalog" class="text-[14px]" :class="active === 'catalog' ? 'text-ink' : 'text-faint'">Katalog</Link>
      <Link href="/tentang" class="text-[14px]" :class="active === 'about' ? 'text-ink' : 'text-faint'">Tentang</Link>
      <Link href="/keranjang" class="relative flex items-center gap-[7px] rounded-pill bg-ink px-[17px] py-[9px] text-[13px] text-canvas">
        Keranjang
        <span class="inline-flex h-[19px] min-w-[19px] items-center justify-center rounded-pill px-[5px] text-[11px] font-semibold" style="background:var(--accent)">{{ cart.count }}</span>
      </Link>
    </nav>
  </header>
</template>
