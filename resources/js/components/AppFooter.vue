<script setup>
import { Link } from '@inertiajs/vue3'
import { useWhatsapp } from '@/lib/whatsapp'

const { href: waHref } = useWhatsapp()
</script>

<template>
  <footer class="border-t border-line bg-panel">
    <div
      class="container-nale grid grid-cols-2 gap-8 py-9 pb-[30px] md:py-[54px]"
      :class="waHref ? 'md:grid-cols-[1.4fr_1fr_1fr]' : 'md:grid-cols-[1.4fr_1fr]'">
      <div class="col-span-2 md:col-span-1">
        <div class="font-display text-[24px] tracking-[0.16em]">NALE</div>
        <p class="mt-3 max-w-[240px] text-[13.5px] leading-[1.6] text-faint">Pakaian anak yang tenang, nyaman, dan dibuat untuk tumbuh bersama mereka.</p>
      </div>
      <div>
        <div class="mb-3.5 text-[12px] uppercase tracking-[0.14em] text-faint">Belanja</div>
        <div class="flex flex-col gap-[9px] text-[13.5px] text-muted">
          <Link href="/katalog" class="text-left text-muted">Semua Produk</Link>
          <Link href="/katalog?type=Setelan" class="text-left text-muted">Setelan</Link>
          <Link href="/katalog?type=Dress" class="text-left text-muted">Dress</Link>
          <Link href="/katalog?type=Atasan" class="text-left text-muted">Atasan</Link>
        </div>
      </div>
      <div v-if="waHref">
        <div class="mb-3.5 text-[12px] uppercase tracking-[0.14em] text-faint">Kontak</div>
        <div class="flex flex-col gap-[9px] text-[13.5px] text-muted">
          <a :href="waHref" target="_blank" rel="noopener" class="text-left text-muted">Tanya via WhatsApp</a>
        </div>
      </div>
    </div>
    <div class="container-nale flex flex-col gap-1.5 border-t border-[#E7E1D7] py-[18px] text-[12px] text-faint md:flex-row md:justify-between md:gap-0">
      <span>© 2026 NALE. Dibuat dengan tenang.</span>
      <span>Bahasa Indonesia · Rp IDR</span>
    </div>
  </footer>
</template>
