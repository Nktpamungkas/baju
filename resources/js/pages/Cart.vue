<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import { useCart, rp } from '@/stores/cart'

const cart = useCart()
const placed = ref(false)

function checkout() {
  // TODO: ganti dengan call ke backend + payment gateway, mis:
  //   router.post('/checkout', { items: cart.items }, { onSuccess: () => { placed.value = true; cart.clear() } })
  placed.value = true
}
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="catalog" />

    <main>
      <section class="mx-auto max-w-[1080px] px-10 pb-[90px] pt-14">
        <span class="eyebrow">Keranjang</span>
        <h1 class="h-display mt-3 text-[46px]">Pesananmu</h1>

        <!-- Empty -->
        <div v-if="cart.items.length === 0" class="mt-10 rounded-lg2 border border-line bg-white px-[30px] py-[60px] text-center">
          <div class="font-display text-[24px] italic text-muted">Keranjangmu masih kosong</div>
          <p class="mb-[26px] mt-2.5 text-[14.5px] text-faint">Yuk, lihat koleksi pakaian anak kami yang nyaman dan tenang.</p>
          <button class="btn-primary" @click="router.visit('/katalog')">Mulai Belanja</button>
        </div>

        <!-- Items + summary -->
        <div v-else class="mt-9 grid grid-cols-[1.5fr_1fr] items-start gap-10">
          <div class="flex flex-col">
            <div v-for="(it, i) in cart.items" :key="it.id + it.variant + it.size" class="flex gap-[18px] border-b border-line py-5">
              <div class="h-[110px] w-[88px] flex-shrink-0 overflow-hidden rounded-card bg-cardbg">
                <img :src="it.img" :alt="it.name" class="h-full w-full object-cover" />
              </div>
              <div class="flex flex-1 flex-col">
                <div class="flex justify-between gap-3">
                  <div>
                    <div class="whitespace-nowrap text-[15.5px] text-ink">{{ it.name }}</div>
                    <div class="mt-[3px] text-[13px] text-faint">{{ it.variant }} · Ukuran {{ it.size }}</div>
                  </div>
                  <button class="self-start text-[12.5px] text-faint" @click="cart.remove(i)">Hapus</button>
                </div>
                <div class="mt-auto flex items-center justify-between pt-3.5">
                  <div class="flex items-center gap-4 rounded-pill border border-[#E2DBD0] px-3.5 py-1.5">
                    <button class="text-[16px] leading-none" @click="cart.dec(i)">−</button>
                    <span class="min-w-[12px] text-center text-[14px]">{{ it.qty }}</span>
                    <button class="text-[16px] leading-none" @click="cart.inc(i)">+</button>
                  </div>
                  <div class="whitespace-nowrap text-[15px] text-ink">{{ rp(it.price * it.qty) }}</div>
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-lg2 border border-line bg-white p-[26px]">
            <div class="mb-[18px] font-display text-[20px] text-ink">Ringkasan</div>
            <div class="flex justify-between py-[7px] text-[14px] text-muted"><span>Subtotal</span><span class="whitespace-nowrap text-ink">{{ rp(cart.subtotal) }}</span></div>
            <div class="flex justify-between py-[7px] text-[14px] text-muted"><span>Pengiriman</span><span class="whitespace-nowrap text-ink">{{ cart.shipping === 0 ? 'Gratis' : rp(cart.shipping) }}</span></div>
            <div class="mt-2 flex justify-between border-t border-line pt-4 text-[17px] text-ink"><span class="font-display">Total</span><span class="whitespace-nowrap font-display">{{ rp(cart.total) }}</span></div>
            <button class="btn-primary mt-[22px] w-full" @click="checkout">Lanjut ke Pembayaran</button>
            <div class="mt-3.5 flex items-center justify-center gap-[7px] whitespace-nowrap text-[12px] text-faint"><span>Transaksi aman</span><span>·</span><span>Bisa COD</span></div>
          </div>
        </div>

        <!-- Confirmation -->
        <div v-if="placed" class="mt-[34px] flex items-center gap-4 rounded-lg2 border border-[#D8E0CF] bg-[#F3F6EC] px-[26px] py-6">
          <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-pill bg-success text-[20px] text-white">✓</div>
          <div>
            <div class="text-[16px] font-medium text-ink">Pesanan diterima — terima kasih!</div>
            <div class="mt-[3px] text-[13.5px] text-muted">Contoh checkout. Hubungkan ke backend + payment gateway untuk transaksi sungguhan.</div>
          </div>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
