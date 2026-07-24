<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import ProductCard from '@/components/ProductCard.vue'
import { rp } from '@/stores/cart'

/**
 * Home page — Editorial hero variant (the recommended default).
 * The prototype NALE.dc.html also has "Galeri" & "Bercerita" variants; build
 * those as separate components or a <component :is> switch if you want them.
 * Controller: return Inertia::render('Home', ['products' => $products]);
 */
const props = defineProps({ products: { type: Array, required: true } })

const featured = computed(() => props.products.slice(0, 4))
const minPrice = computed(() => Math.min(...props.products.map((p) => p.price)))

const TYPE_IMG = {
  Setelan: '/img/koko-olivegreen.jpg',
  Dress: '/img/irina-rosewood.jpg',
  Atasan: '/img/tracy-operamauve.jpg',
  Celana: '/img/celana-sunnycrabby.jpg',
}
const typeCards = computed(() =>
  ['Setelan', 'Dress', 'Atasan', 'Celana'].map((t) => ({
    label: t,
    img: TYPE_IMG[t],
    count: props.products.filter((p) => p.type === t).length,
  }))
)
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="home" />

    <main>
      <!-- Hero -->
      <section class="container-nale grid grid-cols-[1.05fr_0.95fr] items-center gap-14 py-16 pb-16">
        <div>
          <span class="eyebrow">Koleksi NALE</span>
          <h1 class="h-display mt-[18px] text-[62px] leading-[1.04] tracking-[-0.01em]">
            Pakaian yang ikut <em class="italic text-accent">tumbuh</em> bersama mereka.
          </h1>
          <p class="mt-6 max-w-[430px] text-[17px] leading-[1.6] text-muted">
            Katun organik, linen, dan muslin pilihan. Jahitan rapi, warna tenang, dan motif ceria —
            dibuat nyaman untuk gerak aktif anak dari pagi sampai petang.
          </p>
          <div class="mt-[34px] flex gap-3.5">
            <button class="btn-primary" @click="router.visit('/katalog')">Belanja Sekarang</button>
            <button class="btn-ghost" @click="router.visit('/tentang')">Cerita NALE</button>
          </div>
        </div>
        <div class="relative">
          <div class="grid grid-cols-2 grid-rows-2 gap-2.5">
            <img src="/img/orion-denim.jpg" alt="Orion Set" class="aspect-square w-full rounded-lg2 object-cover" />
            <img src="/img/irina-rosewood.jpg" alt="Irina Dress" class="aspect-square w-full rounded-lg2 object-cover" />
            <img src="/img/celana-sunnycrabby.jpg" alt="Celana Gonjreng" class="aspect-square w-full rounded-lg2 object-cover" />
            <img src="/img/koko-olivegreen.jpg" alt="Koko Set" class="aspect-square w-full rounded-lg2 object-cover" />
          </div>
          <div class="absolute -bottom-3.5 -left-3.5 rounded-lg2 bg-canvas px-4 py-[11px]" style="box-shadow:0 8px 28px rgba(28,26,23,.1)">
            <div class="text-[12px] tracking-[0.04em] text-faint">Mulai dari</div>
            <div class="font-display text-[22px] text-ink">{{ rp(minPrice) }}</div>
          </div>
        </div>
      </section>

      <!-- Trust bar -->
      <div class="border-y border-line bg-panel">
        <div class="container-nale flex flex-wrap justify-center gap-12 py-5 text-[13.5px] tracking-[0.04em] text-muted">
          <span>Bahan katun, linen &amp; muslin</span><span class="text-[#D8CFC2]">·</span>
          <span>Pewarna aman Oeko-Tex</span><span class="text-[#D8CFC2]">·</span>
          <span>Jahitan rapi &amp; tahan lama</span>
        </div>
      </div>

      <!-- Featured -->
      <section class="container-nale pb-6 pt-[72px]">
        <div class="mb-[34px] flex items-end justify-between">
          <div>
            <span class="eyebrow">Koleksi Lengkap</span>
            <h2 class="h-display mt-2.5 text-[38px]">Sedang banyak dicari</h2>
          </div>
          <Link href="/katalog" class="border-b border-ink pb-0.5 text-[14px] text-ink">Lihat semua →</Link>
        </div>
        <div class="grid grid-cols-4 gap-x-[22px] gap-y-[34px]">
          <ProductCard v-for="p in featured" :key="p.id" :product="p" />
        </div>
      </section>

      <!-- Categories -->
      <section class="container-nale pb-20 pt-14">
        <span class="eyebrow">Belanja per Kategori</span>
        <div class="mt-[22px] grid grid-cols-4 gap-[18px]">
          <Link
            v-for="c in typeCards" :key="c.label"
            :href="`/katalog?type=${c.label}`"
            class="relative aspect-[4/5] overflow-hidden rounded-card">
            <img :src="c.img" :alt="c.label" class="absolute inset-0 h-full w-full object-cover" />
            <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(28,26,23,.55),rgba(28,26,23,0) 55%)"></div>
            <div class="absolute bottom-4 left-[18px]">
              <div class="font-display text-[24px] text-canvas">{{ c.label }}</div>
              <div class="mt-px text-[12.5px] text-canvas/85">{{ c.count }} produk · Belanja →</div>
            </div>
          </Link>
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
