<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import ProductCard from '@/components/ProductCard.vue'
import { rp } from '@/lib/format'

/**
 * Home page — 3 hero variants (Editorial / Galeri / Bercerita), ported from
 * the original NALE.dc.html prototype design.
 * Controller: return Inertia::render('Home', ['products' => $products]);
 */
const props = defineProps({ products: { type: Array, required: true } })

const variant = ref('editorial') // 'editorial' | 'galeri' | 'bercerita'

const featured = computed(() => props.products.slice(0, 4))
const minPrice = computed(() => Math.min(...props.products.map((p) => p.price)))
const storyFeatures = computed(() => props.products.slice(0, 2))

const typeCards = computed(() =>
  ['Setelan', 'Dress', 'Atasan', 'Celana']
    .map((t) => {
      const matches = props.products.filter((p) => p.type === t)
      return { label: t, img: matches[0]?.variants[0]?.img, count: matches.length }
    })
    .filter((c) => c.img)
)
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="home" />

    <main>
      <!-- Hero variant switcher -->
      <div class="flex justify-center pt-[18px]">
        <div class="inline-flex items-center gap-1 rounded-pill border border-[#E7E1D7] bg-[#F1ECE4] p-[5px]">
          <button
            class="rounded-pill px-4 py-1.5 text-[12.5px] font-medium"
            :class="variant === 'editorial' ? 'bg-ink text-canvas' : 'text-muted'"
            @click="variant = 'editorial'">Editorial</button>
          <button
            class="rounded-pill px-4 py-1.5 text-[12.5px] font-medium"
            :class="variant === 'galeri' ? 'bg-ink text-canvas' : 'text-muted'"
            @click="variant = 'galeri'">Galeri</button>
          <button
            class="rounded-pill px-4 py-1.5 text-[12.5px] font-medium"
            :class="variant === 'bercerita' ? 'bg-ink text-canvas' : 'text-muted'"
            @click="variant = 'bercerita'">Bercerita</button>
        </div>
      </div>

      <!-- VARIANT: Editorial -->
      <template v-if="variant === 'editorial'">
        <section class="container-nale grid grid-cols-1 gap-10 py-10 md:grid-cols-[1.05fr_0.95fr] md:items-center md:gap-14 md:py-16">
          <div>
            <span class="eyebrow">Koleksi NALE</span>
            <h1 class="h-display mt-[18px] text-[36px] leading-[1.08] tracking-[-0.01em] md:text-[62px] md:leading-[1.04]">
              Pakaian yang ikut <em class="italic text-accent">tumbuh</em> bersama mereka.
            </h1>
            <p class="mt-5 max-w-[430px] text-[15px] leading-[1.6] text-muted md:mt-6 md:text-[17px]">
              Katun organik, linen, dan muslin pilihan. Jahitan rapi, warna tenang, dan motif ceria —
              dibuat nyaman untuk gerak aktif anak dari pagi sampai petang.
            </p>
            <div class="mt-7 flex flex-wrap gap-3.5 md:mt-[34px]">
              <button class="btn-primary" @click="router.visit('/katalog')">Belanja Sekarang</button>
              <button class="btn-ghost" @click="router.visit('/tentang')">Cerita NALE</button>
            </div>
          </div>
          <div class="relative">
            <div class="grid grid-cols-2 grid-rows-2 gap-2.5">
              <img v-for="p in featured" :key="p.id" :src="p.variants[0].img" :alt="p.name" class="aspect-square w-full rounded-lg2 object-cover" />
            </div>
            <div class="absolute -bottom-3.5 left-3.5 rounded-lg2 bg-canvas px-4 py-[11px] md:-left-3.5" style="box-shadow:0 8px 28px rgba(28,26,23,.1)">
              <div class="text-[12px] tracking-[0.04em] text-faint">Mulai dari</div>
              <div class="font-display text-[22px] text-ink">{{ rp(minPrice) }}</div>
            </div>
          </div>
        </section>

        <div class="border-y border-line bg-panel">
          <div class="container-nale flex flex-wrap justify-center gap-x-6 gap-y-2 py-5 text-[12.5px] tracking-[0.04em] text-muted md:gap-x-12 md:text-[13.5px]">
            <span>Bahan katun, linen &amp; muslin</span><span class="hidden text-[#D8CFC2] md:inline">·</span>
            <span>Pewarna aman Oeko-Tex</span><span class="hidden text-[#D8CFC2] md:inline">·</span>
            <span>Jahitan rapi &amp; tahan lama</span>
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

        <section class="container-nale pb-14 pt-10 md:pb-20 md:pt-14">
          <span class="eyebrow">Belanja per Kategori</span>
          <div class="mt-4 grid grid-cols-2 gap-3 md:mt-[22px] md:grid-cols-4 md:gap-[18px]">
            <Link
              v-for="c in typeCards" :key="c.label"
              :href="`/katalog?type=${c.label}`"
              class="relative aspect-[4/5] overflow-hidden rounded-card">
              <img :src="c.img" :alt="c.label" class="absolute inset-0 h-full w-full object-cover" />
              <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(28,26,23,.55),rgba(28,26,23,0) 55%)"></div>
              <div class="absolute bottom-3 left-3 md:bottom-4 md:left-[18px]">
                <div class="font-display text-[18px] text-canvas md:text-[24px]">{{ c.label }}</div>
                <div class="mt-px text-[11px] text-canvas/85 md:text-[12.5px]">{{ c.count }} produk · Belanja →</div>
              </div>
            </Link>
          </div>
        </section>
      </template>

      <!-- VARIANT: Galeri -->
      <template v-else-if="variant === 'galeri'">
        <section class="container-nale pb-8 pt-10 text-center md:pb-9 md:pt-[60px]">
          <span class="eyebrow">Koleksi NALE</span>
          <h1 class="h-display mx-auto mt-3.5 max-w-[680px] text-[28px] leading-[1.15] md:text-[48px] md:leading-[1.08]">
            Pilih yang nyaman, biarkan mereka <em class="italic text-accent">bergerak</em>.
          </h1>
          <p class="mx-auto mt-[18px] max-w-[480px] text-[15px] leading-[1.6] text-muted md:text-[16px]">
            Warna tenang dan motif ceria dari bahan pilihan. Geser, lihat, dan temukan favorit mereka.
          </p>
          <button class="btn-primary mt-[26px]" @click="router.visit('/katalog')">Jelajahi Semua</button>
        </section>
        <section class="container-nale pb-16 pt-2 md:pb-[90px]">
          <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
            <ProductCard v-for="p in products" :key="p.id" :product="p" />
          </div>
        </section>
      </template>

      <!-- VARIANT: Bercerita -->
      <template v-else>
        <section class="bg-ink">
          <div class="mx-auto max-w-[880px] px-5 py-14 pb-14 text-center md:px-10 md:py-[90px] md:pb-20">
            <span class="text-[11px] uppercase tracking-[0.3em] text-accentsoft md:text-[12px]">Cerita NALE</span>
            <h1 class="h-display mt-4 text-[30px] leading-[1.15] text-canvas md:mt-[22px] md:text-[58px] md:leading-[1.08]">
              Bukan sekadar pakaian — <em class="italic text-accentsoft">teman tumbuh</em> mereka.
            </h1>
            <p class="mx-auto mt-5 max-w-[560px] text-[15px] leading-[1.65] md:mt-6 md:text-[17px] md:leading-[1.7]" style="color:#C9C3B8">
              Setiap potong dibuat tenang: bahan pilihan, jahitan rapi, dan warna yang mudah dipadukan
              dari pagi sampai petang.
            </p>
            <button class="mt-7 rounded-pill bg-canvas px-8 py-[15px] text-[15px] font-medium text-ink md:mt-8" @click="router.visit('/katalog')">Lihat Koleksi</button>
          </div>
        </section>

        <section class="container-nale flex flex-col gap-10 pb-6 pt-10 md:gap-16 md:pb-10 md:pt-16">
          <div
            v-for="(f, i) in storyFeatures" :key="f.id"
            class="grid cursor-pointer grid-cols-1 items-center gap-5 md:grid-cols-2 md:gap-12"
            @click="router.visit(`/produk/${f.id}`)">
            <div class="aspect-[4/3] overflow-hidden rounded-lg2" :class="i % 2 === 1 ? 'md:order-2' : ''">
              <img :src="f.variants[0].img" :alt="f.name" class="h-full w-full object-cover" />
            </div>
            <div>
              <span class="eyebrow">{{ f.type }}</span>
              <h2 class="h-display mt-3 text-[24px] leading-[1.15] md:text-[34px] md:leading-[1.1]">{{ f.name }}</h2>
              <p class="mt-3.5 text-[15px] leading-[1.6] text-muted md:text-[16px] md:leading-[1.65]">{{ f.desc }}</p>
              <div class="mt-4 font-display text-[20px] text-ink">{{ rp(f.price) }}</div>
            </div>
          </div>
        </section>

        <section class="container-nale pb-16 pt-3 md:pb-[90px] md:pt-[14px]">
          <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-[18px]">
            <Link
              v-for="c in typeCards" :key="c.label"
              :href="`/katalog?type=${c.label}`"
              class="relative aspect-[4/5] overflow-hidden rounded-card">
              <img :src="c.img" :alt="c.label" class="absolute inset-0 h-full w-full object-cover" />
              <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(28,26,23,.55),rgba(28,26,23,0) 55%)"></div>
              <div class="absolute bottom-3 left-3 md:bottom-4 md:left-[18px]">
                <div class="font-display text-[18px] text-canvas md:text-[24px]">{{ c.label }}</div>
                <div class="mt-px text-[11px] text-canvas/85 md:text-[12.5px]">{{ c.count }} produk · Belanja →</div>
              </div>
            </Link>
          </div>
        </section>
      </template>
    </main>

    <AppFooter />
  </div>
</template>
