<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useWhatsapp } from '@/lib/whatsapp'
import Icon from '@/components/Icon.vue'

const { href: waHref } = useWhatsapp()
const page = usePage()
const instagram = computed(() => page.props.instagram)
</script>

<template>
  <footer class="border-t border-line bg-panel">
    <div class="container-nale flex flex-wrap items-center justify-center gap-x-6 gap-y-2 border-b border-[#E7E1D7] py-4 text-[12px] text-faint">
      <span class="flex items-center gap-1.5"><Icon name="shield" :size="16" />Checkout Aman</span>
      <span class="flex items-center gap-1.5"><Icon name="truck" :size="16" />Kirim ke Seluruh Indonesia</span>
      <span class="flex items-center gap-1.5"><Icon name="heart" :size="16" />Bahan Aman untuk Anak</span>
    </div>

    <div
      class="container-nale grid grid-cols-2 gap-8 py-9 pb-[30px] md:py-[54px]"
      :class="waHref ? 'md:grid-cols-[1.4fr_1fr_1fr]' : 'md:grid-cols-[1.4fr_1fr]'">
      <div class="col-span-2 md:col-span-1">
        <div class="font-display text-[24px] tracking-[0.16em]">NALE</div>
        <p class="mt-3 max-w-[240px] text-[13.5px] leading-[1.6] text-faint">Pakaian anak yang tenang, nyaman, dan dibuat untuk tumbuh bersama mereka.</p>
        <div class="mt-4 flex items-center gap-3">
          <a v-if="instagram" :href="instagram" target="_blank" rel="noopener" class="text-faint hover:text-ink"><Icon name="instagram" /></a>
          <a v-if="waHref" :href="waHref" target="_blank" rel="noopener" class="text-faint hover:text-ink">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-8.6 15L2 22l5.2-1.4A10 10 0 1012 2zm5.7 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .1-3.2-.7-2.7-1.1-4.4-3.8-4.6-4a5.3 5.3 0 01-1.1-2.9c0-1 .5-1.6.7-1.8.2-.2.5-.3.6-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .6l-.4.5c-.1.2-.3.3-.1.6.2.4.9 1.5 2 2.4 1.3 1.1 2.4 1.5 2.8 1.6.3.1.5.1.7-.1l.6-.7c.2-.3.4-.2.6-.1l1.9.9c.2.1.4.2.4.4.1.2.1.7-.1 1.3z" /></svg>
          </a>
        </div>
      </div>
      <div>
        <div class="mb-3.5 text-[12px] uppercase tracking-[0.14em] text-faint">Belanja</div>
        <div class="flex flex-col gap-[9px] text-[13.5px] text-muted">
          <Link href="/katalog" class="text-left text-muted">Semua Produk</Link>
          <Link href="/katalog?type=Setelan" class="text-left text-muted">Setelan</Link>
          <Link href="/katalog?type=Dress" class="text-left text-muted">Dress</Link>
          <Link href="/katalog?type=Atasan" class="text-left text-muted">Atasan</Link>
          <Link href="/pesanan-saya" class="text-left text-muted">Cek Pesanan</Link>
        </div>
      </div>
      <div v-if="waHref">
        <div class="mb-3.5 text-[12px] uppercase tracking-[0.14em] text-faint">Kontak</div>
        <div class="flex flex-col gap-[9px] text-[13.5px] text-muted">
          <a :href="waHref" target="_blank" rel="noopener" class="text-left text-muted">Tanya via WhatsApp</a>
        </div>
        <div class="mt-5 text-[12px] uppercase tracking-[0.14em] text-faint">Pembayaran</div>
        <div class="mt-2.5 flex gap-2 text-[11px] text-muted">
          <span class="rounded-lg2 border px-2.5 py-1" style="border-color:#DDD5C9">Transfer Bank</span>
          <span class="rounded-lg2 border px-2.5 py-1" style="border-color:#DDD5C9">QRIS</span>
        </div>
      </div>
    </div>
    <div class="container-nale flex flex-col gap-1.5 border-t border-[#E7E1D7] py-[18px] text-[12px] text-faint md:flex-row md:justify-between md:gap-0">
      <span>© 2026 NALE. Dibuat dengan tenang.</span>
      <span>Bahasa Indonesia · Rp IDR</span>
    </div>
  </footer>
</template>
