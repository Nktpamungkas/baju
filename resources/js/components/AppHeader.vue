<script setup>
import { Link } from '@inertiajs/vue3'
defineProps({ active: { type: String, default: 'home' } }) // 'home' | 'catalog' | 'about'
</script>

<template>
  <header class="sticky top-0 z-50 flex items-center justify-between border-b border-line px-10 py-[18px] backdrop-blur-md" style="background:rgba(251,250,248,.82)">
    <Link href="/" class="flex items-center gap-2">
      <img src="/img/mascot-small.png" alt="" class="h-16 w-16 object-contain" />
      <span class="flex flex-col items-start gap-px">
        <span class="font-display text-[26px] leading-none tracking-[0.16em] text-ink">NALE</span>
        <span class="ml-0.5 text-[9px] uppercase tracking-[0.32em] text-faint">Baju Anak</span>
      </span>
    </Link>

    <nav class="flex items-center gap-[34px]">
      <Link href="/" class="text-[14px]" :class="active === 'home' ? 'text-ink' : 'text-faint'">Beranda</Link>
      <Link href="/katalog" class="text-[14px]" :class="active === 'catalog' ? 'text-ink' : 'text-faint'">Katalog</Link>
      <Link href="/tentang" class="text-[14px]" :class="active === 'about' ? 'text-ink' : 'text-faint'">Tentang</Link>
    </nav>
  </header>
</template>
