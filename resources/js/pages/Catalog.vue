<script setup>
import { ref, computed } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
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

const filtered = computed(() =>
  active.value === 'Semua' ? props.products : props.products.filter((p) => p.type === active.value)
)
const title = computed(() => (active.value === 'Semua' ? 'Semua Produk' : active.value))
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="catalog" />

    <main>
      <section class="container-nale pt-8 md:pt-14">
        <span class="eyebrow">Katalog</span>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-4 md:gap-6">
          <h1 class="h-display whitespace-nowrap text-[28px] leading-[1.05] md:text-[46px]">{{ title }}</h1>
          <span class="pb-2 text-[13px] text-faint md:text-[13.5px]">{{ filtered.length }} produk</span>
        </div>
        <div class="mt-5 flex flex-wrap gap-2 md:mt-[26px] md:gap-2.5">
          <button
            v-for="t in TYPES" :key="t"
            class="rounded-pill border px-4 py-2 text-[13px] md:px-5 md:py-[9px] md:text-[13.5px]"
            :class="active === t ? 'border-ink bg-ink text-canvas' : 'border-[#D9D2C7] text-muted'"
            @click="active = t">{{ t }}</button>
        </div>
      </section>

      <section class="container-nale pb-16 pt-6 md:pb-[90px] md:pt-[34px]">
        <div class="grid grid-cols-2 gap-x-4 gap-y-6 md:grid-cols-4 md:gap-x-[22px] md:gap-y-9">
          <ProductCard v-for="p in filtered" :key="p.id" :product="p" />
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
