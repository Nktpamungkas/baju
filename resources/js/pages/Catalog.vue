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
      <section class="container-nale pt-14">
        <span class="eyebrow">Katalog</span>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-6">
          <h1 class="h-display whitespace-nowrap text-[46px] leading-[1.05]">{{ title }}</h1>
          <span class="pb-2 text-[13.5px] text-faint">{{ filtered.length }} produk</span>
        </div>
        <div class="mt-[26px] flex flex-wrap gap-2.5">
          <button
            v-for="t in TYPES" :key="t"
            class="rounded-pill border px-5 py-[9px] text-[13.5px]"
            :class="active === t ? 'border-ink bg-ink text-canvas' : 'border-[#D9D2C7] text-muted'"
            @click="active = t">{{ t }}</button>
        </div>
      </section>

      <section class="container-nale pb-[90px] pt-[34px]">
        <div class="grid grid-cols-4 gap-x-[22px] gap-y-9">
          <ProductCard v-for="p in filtered" :key="p.id" :product="p" />
        </div>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
