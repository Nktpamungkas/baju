<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import ProductCard from '@/components/ProductCard.vue'
import { rp } from '@/lib/format'

/**
 * Product detail page (Inertia). In your Laravel controller:
 *   return Inertia::render('Product', ['product' => $product, 'related' => $related]);
 * `product` shape matches handoff/data/products.json.
 */
const props = defineProps({
  product: { type: Object, required: true },
  related: { type: Array, default: () => [] },
})

const varIdx = ref(0)

const currentVariant = computed(() => props.product.variants[varIdx.value])
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="catalog" />

    <main>
      <section class="container-nale pt-7">
        <button class="text-[13.5px] tracking-[0.02em] text-muted" @click="router.visit('/katalog')">← Kembali ke katalog</button>
      </section>

      <section class="container-nale grid grid-cols-1 items-start gap-8 py-6 pb-10 md:grid-cols-2 md:gap-14 md:pb-14">
        <!-- Gallery -->
        <div>
          <div class="aspect-[4/5] overflow-hidden rounded-lg2 bg-cardbg">
            <img :src="currentVariant.img" :alt="product.name" class="h-full w-full object-cover" />
          </div>
          <div class="mt-3 flex gap-2.5">
            <button
              v-for="(v, i) in product.variants" :key="v.name"
              class="h-16 w-[54px] flex-shrink-0 overflow-hidden rounded-card border-2 bg-cardbg md:h-20 md:w-[66px]"
              :style="{ borderColor: i === varIdx ? '#1C1A17' : 'transparent' }"
              @click="varIdx = i">
              <img :src="v.img" :alt="v.name" class="h-full w-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Info -->
        <div class="md:pt-1.5">
          <span class="eyebrow">{{ product.type }}</span>
          <h1 class="h-display mt-3 text-[28px] leading-[1.1] md:text-[44px] md:leading-[1.06]">{{ product.name }}</h1>
          <div class="mt-3.5 font-display text-[26px] text-ink">{{ rp(product.price) }}</div>
          <p class="mt-5 text-[16px] leading-[1.65] text-muted">{{ product.desc }}</p>

          <!-- Variant -->
          <div class="mt-7">
            <div class="mb-3 flex items-baseline justify-between">
              <span class="text-[12px] uppercase tracking-[0.14em] text-faint">{{ product.word }}</span>
              <span class="text-[13.5px] text-ink">{{ currentVariant.name }}</span>
            </div>
            <div class="flex flex-wrap gap-2.5">
              <button
                v-for="(v, i) in product.variants" :key="v.name"
                class="rounded-lg2 border px-3.5 py-2 text-[13px]"
                :class="i === varIdx ? 'border-ink bg-ink text-canvas' : 'border-[#D9D2C7] text-[#3A372F]'"
                @click="varIdx = i">{{ v.name }}</button>
            </div>
          </div>

          <!-- Beli via marketplace -->
          <div class="mt-7 flex items-center gap-3">
            <a :href="product.shopee || '#'" :target="product.shopee ? '_blank' : '_self'" rel="noopener"
              class="btn-primary flex-1 whitespace-nowrap text-center no-underline"
              :class="{ 'pointer-events-none opacity-40': !product.shopee }">Beli di Shopee</a>
            <a :href="product.toko || '#'" :target="product.toko ? '_blank' : '_self'" rel="noopener"
              class="btn-ghost flex-1 whitespace-nowrap text-center no-underline"
              :class="{ 'pointer-events-none opacity-40': !product.toko }">Beli di Tokopedia</a>
          </div>
        </div>
      </section>

      <!-- Related -->
      <section v-if="related.length" class="container-nale pb-16 pt-2 md:pb-[90px]">
        <h3 class="h-display mb-5 text-[22px] md:mb-6 md:text-[28px]">Mungkin cocok juga</h3>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-[22px]">
          <ProductCard v-for="p in related" :key="p.id" :product="p" />
        </div>
      </section>
    </main>
  </div>
</template>
