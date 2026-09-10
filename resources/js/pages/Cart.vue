<script setup>
import { router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import Icon from '@/components/Icon.vue'
import { useCart } from '@/lib/cart'
import { rp } from '@/lib/format'

const cart = useCart()
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="cart" />

    <main>
      <section class="container-nale pb-16 pt-8 md:pb-[90px] md:pt-14">
        <span class="eyebrow">Keranjang</span>
        <div class="mt-3 flex flex-wrap items-end gap-3">
          <h1 class="h-display text-[28px] md:text-[46px]">Pesananmu</h1>
          <span v-if="cart.state.items.length" class="pb-1.5 text-[13px] text-faint md:pb-2.5 md:text-[13.5px]">{{ cart.state.items.length }} item</span>
        </div>

        <div v-if="cart.state.items.length === 0" class="mt-8 rounded-lg2 border border-line bg-white px-6 py-16 text-center md:mt-10 md:px-[30px] md:py-20">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-pill" style="background:var(--accent-soft);color:var(--accent)">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h2l2.4 12.4a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L21 8H6" /><circle cx="10" cy="21" r="1.4" fill="currentColor" stroke="none" /><circle cx="18" cy="21" r="1.4" fill="currentColor" stroke="none" /></svg>
          </div>
          <div class="mt-4 font-display text-[19px] italic text-muted md:text-[24px]">Keranjangmu masih kosong</div>
          <p class="mb-6 mt-2.5 text-[13.5px] text-faint md:mb-[26px] md:text-[14.5px]">Yuk, lihat koleksi pakaian anak kami yang nyaman dan tenang.</p>
          <button class="btn-primary" @click="router.visit('/katalog')">Mulai Belanja</button>
        </div>

        <div v-else class="mt-7 grid grid-cols-1 items-start gap-6 md:mt-9 md:grid-cols-[1.5fr_1fr] md:gap-10">
          <div class="flex flex-col">
            <div v-for="(it, i) in cart.state.items" :key="it.id + it.variant + it.size" class="flex gap-3.5 border-b border-line py-4 md:gap-[18px] md:py-5">
              <div class="h-[92px] w-[74px] flex-shrink-0 overflow-hidden rounded-card bg-cardbg md:h-[110px] md:w-[88px]">
                <img :src="it.img" :alt="it.name" class="h-full w-full object-cover" />
              </div>
              <div class="flex flex-1 flex-col">
                <div class="flex justify-between gap-3">
                  <div>
                    <div class="text-[14px] text-ink md:text-[15.5px]">{{ it.name }}</div>
                    <div class="mt-[3px] text-[12px] text-faint md:text-[13px]">{{ it.variant }} · Ukuran {{ it.size }}</div>
                  </div>
                  <button class="self-start text-[12px] text-faint md:text-[12.5px]" @click="cart.remove(i)">Hapus</button>
                </div>
                <div class="mt-auto flex items-center justify-between pt-3">
                  <div class="flex items-center gap-3 rounded-pill border border-[#E2DBD0] px-3 py-1 md:gap-4 md:px-3.5 md:py-1.5">
                    <button class="text-[16px] leading-none" @click="cart.dec(i)">−</button>
                    <span class="min-w-[12px] text-center text-[13.5px] md:text-[14px]">{{ it.qty }}</span>
                    <button class="text-[16px] leading-none" @click="cart.inc(i)">+</button>
                  </div>
                  <div class="whitespace-nowrap text-[14px] text-ink md:text-[15px]">{{ rp(it.price * it.qty) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-lg2 border border-line bg-white p-5 shadow-lg shadow-black/[0.03] md:p-[26px]">
            <div class="mb-4 font-display text-[18px] text-ink md:mb-[18px] md:text-[20px]">Ringkasan</div>
            <div class="flex justify-between py-[7px] text-[13.5px] text-muted md:text-[14px]"><span>Subtotal</span><span class="whitespace-nowrap text-ink">{{ rp(cart.subtotal.value) }}</span></div>
            <div v-if="cart.state.discountAmount" class="flex justify-between py-[7px] text-[13.5px] text-muted md:text-[14px]"><span>Diskon ({{ cart.state.discountCode }})</span><span class="whitespace-nowrap text-ink">−{{ rp(cart.state.discountAmount) }}</span></div>
            <div class="mt-2 flex justify-between border-t border-line pt-4 text-[16px] text-ink md:text-[17px]"><span class="font-display">Perkiraan Total</span><span class="whitespace-nowrap font-display">{{ rp(Math.max(0, cart.subtotal.value - cart.state.discountAmount)) }}</span></div>
            <p class="mt-2 text-[11.5px] text-faint">Ongkos kirim dihitung di halaman checkout sesuai alamat tujuan.</p>
            <button class="btn-primary mt-5 w-full md:mt-[22px]" @click="router.visit('/checkout')">Lanjut ke Checkout</button>
            <div class="mt-4 flex items-center justify-center gap-4 border-t border-line pt-4 text-[11px] text-faint">
              <span class="flex items-center gap-1.5"><Icon name="shield" :size="14" />Checkout Aman</span>
              <span class="flex items-center gap-1.5"><Icon name="truck" :size="14" />Dilacak Real-time</span>
            </div>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
