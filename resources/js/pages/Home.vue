<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import FloatingWhatsApp from '@/components/FloatingWhatsApp.vue'
import ProductCard from '@/components/ProductCard.vue'
import Icon from '@/components/Icon.vue'
import { rp } from '@/lib/format'

const props = defineProps({
  products: { type: Array, required: true },
  discount: { type: Object, default: null },
  reviews: { type: Array, default: () => [] },
})

const featured = computed(() => props.products.slice(0, 4))
const minPrice = computed(() => Math.min(...props.products.map((p) => p.price)))

const typeCards = computed(() =>
  ['Setelan', 'Dress', 'Atasan', 'Celana']
    .map((t) => {
      const matches = props.products.filter((p) => p.type === t)
      return { label: t, img: matches[0]?.variants[0]?.img, count: matches.length }
    })
    .filter((c) => c.img)
)

const TRUST_POINTS = [
  { icon: 'heart', title: 'Bahan Aman untuk Anak', desc: 'Katun, linen & muslin, pewarna Oeko-Tex.' },
  { icon: 'shield', title: 'Checkout Aman', desc: 'Transfer bank / QRIS, dikonfirmasi manual.' },
  { icon: 'truck', title: 'Kirim ke Seluruh Indonesia', desc: 'Ongkir dihitung otomatis, banyak pilihan kurir.' },
  { icon: 'pin', title: 'Bisa Dilacak Real-time', desc: 'Pantau status pesanan sampai barang diterima.' },
]

function discountLabel(d) {
  return d.type === 'percent' ? `${d.value}%` : rp(d.value)
}
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="home" />

    <div v-if="discount" class="flex flex-wrap items-center justify-center gap-2 px-4 py-2.5 text-center text-[12.5px] text-canvas md:text-[13.5px]" style="background:var(--accent)">
      <span>🎉 Pakai kode <b class="tracking-[0.04em]">{{ discount.code }}</b> — diskon {{ discountLabel(discount) }} untuk pembelian sekarang.</span>
    </div>

    <main>
      <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute -right-24 -top-32 h-[420px] w-[420px] rounded-pill md:-right-10 md:-top-40 md:h-[560px] md:w-[560px]" style="background:radial-gradient(closest-side, var(--accent-soft), transparent)"></div>
        <div class="pointer-events-none absolute -left-32 bottom-0 h-[280px] w-[280px] rounded-pill" style="background:radial-gradient(closest-side, var(--accent-soft), transparent);opacity:.6"></div>

        <section class="container-nale relative grid grid-cols-1 gap-10 py-12 md:grid-cols-[1.05fr_0.95fr] md:items-center md:gap-14 md:py-20">
          <div>
            <span class="eyebrow rounded-pill px-3 py-1.5" style="background:var(--accent-soft);color:var(--accent)">✂ Koleksi NALE</span>
            <h1 class="h-display mt-5 text-[38px] leading-[1.06] tracking-[-0.01em] md:text-[68px] md:leading-[1.02]">
              Pakaian yang ikut <em class="italic text-accent">tumbuh</em> bersama mereka.
            </h1>
            <p class="mt-5 max-w-[430px] text-[15px] leading-[1.6] text-muted md:mt-6 md:text-[17px]">
              Katun organik, linen, dan muslin pilihan. Jahitan rapi, warna tenang, dan motif ceria —
              dibuat nyaman untuk gerak aktif anak dari pagi sampai petang.
            </p>
            <div class="mt-7 flex flex-wrap gap-3.5 md:mt-[34px]">
              <button class="btn-primary shadow-lg shadow-black/5" @click="router.visit('/katalog')">Belanja Sekarang</button>
              <button class="btn-ghost bg-canvas" @click="router.visit('/tentang')">Cerita NALE</button>
            </div>
            <div class="mt-8 flex items-center gap-5 text-[12.5px] text-muted md:mt-10">
              <span><b class="font-display text-[20px] text-ink">{{ products.length }}+</b> Model</span>
              <span class="h-6 w-px bg-line"></span>
              <span><b class="font-display text-[20px] text-ink">{{ typeCards.length }}</b> Kategori</span>
              <span class="h-6 w-px bg-line"></span>
              <span><b class="font-display text-[20px] text-ink">100%</b> Katun &amp; Linen</span>
            </div>
          </div>
          <div class="relative">
            <div class="grid grid-cols-2 grid-rows-2 gap-3.5">
              <img v-for="p in featured" :key="p.id" :src="p.variants[0].img" :alt="p.name" class="aspect-square w-full rounded-lg2 object-cover shadow-xl shadow-black/5" />
            </div>
            <div class="absolute -bottom-4 left-4 rounded-lg2 bg-canvas px-5 py-3 md:-left-5" style="box-shadow:0 12px 32px rgba(28,26,23,.14)">
              <div class="text-[12px] tracking-[0.04em] text-faint">Mulai dari</div>
              <div class="font-display text-[24px] text-ink">{{ rp(minPrice) }}</div>
            </div>
          </div>
        </section>
      </div>

      <div class="border-y border-line bg-panel">
        <div class="container-nale grid grid-cols-2 gap-x-4 gap-y-6 py-8 md:grid-cols-4 md:gap-6 md:py-10">
          <div v-for="t in TRUST_POINTS" :key="t.title" class="flex flex-col items-start gap-2.5 md:items-center md:text-center">
            <div class="flex h-10 w-10 items-center justify-center rounded-pill" style="background:var(--accent-soft);color:var(--accent)">
              <Icon :name="t.icon" />
            </div>
            <div>
              <div class="text-[13px] text-ink md:text-[13.5px]">{{ t.title }}</div>
              <div class="mt-0.5 text-[11.5px] leading-[1.4] text-faint">{{ t.desc }}</div>
            </div>
          </div>
        </div>
      </div>

      <section class="container-nale pb-6 pt-10 md:pt-[72px]">
        <div class="mb-6 flex items-end justify-between md:mb-[34px]">
          <div>
            <span class="eyebrow">Koleksi Lengkap</span>
            <h2 class="h-display mt-2.5 text-[26px] md:text-[38px]">Sedang banyak dicari</h2>
          </div>
          <Link href="/katalog" class="border-b border-ink pb-0.5 text-[13px] text-ink md:text-[14px]">Lihat semua →</Link>
        </div>
        <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4 md:gap-x-[22px] md:gap-y-[34px]">
          <ProductCard v-for="p in featured" :key="p.id" :product="p" />
        </div>
      </section>

      <section class="border-y border-line py-12 md:py-16" style="background:#FBF2EC">
        <div class="container-nale">
          <span class="eyebrow">Belanja per Kategori</span>
          <h2 class="h-display mt-2.5 text-[26px] md:text-[38px]">Cari sesuai kebutuhan</h2>
          <div class="mt-6 grid grid-cols-2 gap-3.5 md:mt-9 md:grid-cols-4 md:gap-5">
            <Link
              v-for="c in typeCards" :key="c.label"
              :href="`/katalog?type=${c.label}`"
              class="group relative aspect-[4/5] overflow-hidden rounded-card shadow-lg shadow-black/5">
              <img :src="c.img" :alt="c.label" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105" />
              <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(28,26,23,.6),rgba(28,26,23,0) 55%)"></div>
              <div class="absolute bottom-3.5 left-3.5 md:bottom-5 md:left-5">
                <div class="font-display text-[19px] text-canvas md:text-[26px]">{{ c.label }}</div>
                <div class="mt-px text-[11px] text-canvas/85 md:text-[12.5px]">{{ c.count }} produk · Belanja →</div>
              </div>
            </Link>
          </div>
        </div>
      </section>

      <section v-if="reviews.length" class="border-t border-line bg-panel py-12 md:py-[68px]">
        <div class="container-nale">
          <span class="eyebrow">Apa Kata Pembeli</span>
          <h2 class="h-display mt-2.5 text-[26px] md:text-[38px]">Dipercaya pembeli yang sudah menerima pesanannya</h2>
          <div class="mt-7 grid grid-cols-1 gap-4 md:mt-9 md:grid-cols-3 md:gap-5">
            <div v-for="r in reviews" :key="r.id" class="rounded-lg2 border bg-canvas p-5" style="border-color:#E9E3D9">
              <div class="text-[13px] text-accent">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</div>
              <p class="mt-2.5 text-[13.5px] leading-[1.6] text-muted">&ldquo;{{ r.comment }}&rdquo;</p>
              <div class="mt-3 text-[12.5px] text-ink">{{ r.customer_name }}</div>
            </div>
          </div>
        </div>
      </section>

      <section class="px-4 py-14 text-center md:py-20" style="background:var(--accent)">
        <h2 class="h-display text-[28px] leading-[1.15] text-canvas md:text-[42px]" style="color:#FBFAF8">Yuk, lengkapi lemari si kecil.</h2>
        <p class="mx-auto mt-3 max-w-[380px] text-[14px] leading-[1.6] md:text-[15.5px]" style="color:rgba(251,250,248,.85)">
          Belanja langsung dari kami, tanpa potongan marketplace — proses cepat, bisa dilacak sampai sampai di rumah.
        </p>
        <button class="btn-primary mt-7 bg-canvas text-ink" @click="router.visit('/katalog')">Lihat Semua Produk</button>
      </section>
    </main>

    <AppFooter />
    <FloatingWhatsApp />
  </div>
</template>
