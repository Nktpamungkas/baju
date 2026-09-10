<script setup>
import { Link } from '@inertiajs/vue3'
import { useCart } from '@/lib/cart'
defineProps({ active: { type: String, default: 'home' } }) // 'home' | 'catalog' | 'about' | 'lookup' | 'cart'
const cart = useCart()
</script>

<template>
  <header class="sticky top-0 z-50 flex items-center justify-between border-b border-line px-4 py-3 backdrop-blur-md md:px-10 md:py-[18px]" style="background:rgba(251,250,248,.82)">
    <Link href="/" class="flex items-center gap-1.5 md:gap-2">
      <img src="/img/mascot-small.png" alt="" class="h-9 w-9 object-contain md:h-16 md:w-16" />
      <span class="flex flex-col items-start gap-px">
        <span class="font-display text-[18px] leading-none tracking-[0.16em] text-ink md:text-[26px]">NALE</span>
        <span class="ml-0.5 hidden text-[9px] uppercase tracking-[0.32em] text-faint md:block">Baju Anak</span>
      </span>
    </Link>

    <nav class="flex items-center gap-3 md:gap-[34px]">
      <Link href="/" class="text-[12.5px] md:text-[14px]" :class="active === 'home' ? 'text-ink' : 'text-faint'">Beranda</Link>
      <Link href="/katalog" class="text-[12.5px] md:text-[14px]" :class="active === 'catalog' ? 'text-ink' : 'text-faint'">Katalog</Link>
      <Link href="/tentang" class="text-[12.5px] md:text-[14px]" :class="active === 'about' ? 'text-ink' : 'text-faint'">Tentang</Link>
      <Link href="/pesanan-saya" class="hidden text-[12.5px] md:block md:text-[14px]" :class="active === 'lookup' ? 'text-ink' : 'text-faint'">Cek Pesanan</Link>
      <Link href="/keranjang" class="relative flex items-center" :class="active === 'cart' ? 'text-ink' : 'text-faint'">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h2l2.4 12.4a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L21 8H6" /><circle cx="10" cy="21" r="1.4" fill="currentColor" stroke="none" /><circle cx="18" cy="21" r="1.4" fill="currentColor" stroke="none" /></svg>
        <span v-if="cart.count.value > 0" class="absolute -right-2 -top-2 flex h-4 w-4 items-center justify-center rounded-pill text-[9px] text-white" style="background:var(--accent)">{{ cart.count.value }}</span>
      </Link>
    </nav>
  </header>
</template>
