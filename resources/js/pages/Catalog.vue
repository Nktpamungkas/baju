<script setup>
import { ref, computed } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import FloatingWhatsApp from '@/components/FloatingWhatsApp.vue'
import ProductCard from '@/components/ProductCard.vue'

/**
 * Catalog page. Controller:
 *   return Inertia::render('Catalog', ['products' => $products, 'type' => $type]);
 * `products` = array sesuai data/products.json. `type` = filter awal (opsional).
 */
const props = defineProps({
  products: { type: Array, required: true },
  type: { type: String, default: 'Semua' },
})

const TYPES = ['Semua', 'Setelan', 'Dress', 'Atasan', 'Celana']
const active = ref(props.type)
const search = ref('')
const sort = ref('default')

const filtered = computed(() => {
  let list = active.value === 'Semua' ? props.products : props.products.filter((p) => p.type === active.value)

  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter((p) => p.name.toLowerCase().includes(q))

  if (sort.value === 'price_asc') list = [...list].sort((a, b) => a.price - b.price)
  else if (sort.value === 'price_desc') list = [...list].sort((a, b) => b.price - a.price)

  return list
})
const title = computed(() => (active.value === 'Semua' ? 'Semua Produk' : active.value))
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="catalog" />

    <main>
      <div class="relative overflow-hidden border-b border-line" style="background:#FBF2EC">
        <div class="pointer-events-none absolute -right-20 -top-24 h-[320px] w-[320px] rounded-pill md:h-[380px] md:w-[380px]" style="background:radial-gradient(closest-side, var(--accent-soft), transparent);opacity:.7"></div>
        <section class="container-nale relative pb-7 pt-10 md:pb-9 md:pt-16">
          <span class="eyebrow">Katalog</span>
          <div class="mt-3 flex flex-wrap items-end justify-between gap-4 md:gap-6">
            <h1 class="h-display whitespace-nowrap text-[32px] leading-[1.05] md:text-[52px]">{{ title }}</h1>
            <span class="pb-2 text-[13px] text-faint md:text-[13.5px]">{{ filtered.length }} produk</span>
          </div>
          <div class="mt-5 flex flex-wrap gap-2 md:mt-[26px] md:gap-2.5">
            <button
              v-for="t in TYPES" :key="t"
              class="rounded-pill border px-4 py-2 text-[13px] transition md:px-5 md:py-[9px] md:text-[13.5px]"
              :class="active === t ? 'border-ink bg-ink text-canvas' : 'border-[#D9D2C7] bg-canvas text-muted hover:border-ink'"
              @click="active = t">{{ t }}</button>
          </div>
          <div class="mt-4 flex flex-wrap gap-3 md:mt-5">
            <input
              v-model="search" type="search" placeholder="Cari nama produk..."
              class="min-w-0 flex-1 rounded-pill border border-[#D9D2C7] bg-canvas px-4 py-2 text-[13px] md:max-w-xs md:text-[13.5px]">
            <select
              v-model="sort"
              class="rounded-pill border border-[#D9D2C7] bg-canvas px-4 py-2 text-[13px] text-muted md:text-[13.5px]">
              <option value="default">Urutan Default</option>
              <option value="price_asc">Harga Terendah</option>
              <option value="price_desc">Harga Tertinggi</option>
            </select>
          </div>
        </section>
      </div>

      <section class="container-nale pb-16 pt-6 md:pb-[90px] md:pt-[34px]">
        <p v-if="filtered.length === 0" class="py-16 text-center text-[14px] text-faint">
          Produk tidak ditemukan.
        </p>
        <div v-else class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4 md:gap-x-[22px] md:gap-y-9">
          <ProductCard v-for="p in filtered" :key="p.id" :product="p" />
        </div>
      </section>
    </main>

    <AppFooter />
    <FloatingWhatsApp />
  </div>
</template>
