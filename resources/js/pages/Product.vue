<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import ProductCard from '@/components/ProductCard.vue'
import { useCart, rp } from '@/stores/cart'

/**
 * Product detail page (Inertia). In your Laravel controller:
 *   return Inertia::render('Product', ['product' => $product, 'related' => $related]);
 * `product` shape matches handoff/data/products.json.
 */
const props = defineProps({
  product: { type: Object, required: true },
  related: { type: Array, default: () => [] },
})

const cart = useCart()
const varIdx = ref(0)
const size = ref(null)
const qty = ref(1)

const currentVariant = computed(() => props.product.variants[varIdx.value])
const SIZES = ['S', 'M', 'L']

function addToCart() {
  cart.add({
    id: props.product.id,
    name: props.product.name,
    variant: currentVariant.value.name,
    size: size.value || 'M',
    price: props.product.price,
    img: currentVariant.value.img,
    qty: qty.value,
  })
  router.visit('/keranjang')
}
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="catalog" />

    <main>
      <section class="container-nale pt-7">
        <button class="text-[13.5px] tracking-[0.02em] text-muted" @click="router.visit('/katalog')">← Kembali ke katalog</button>
      </section>

      <section class="container-nale grid grid-cols-2 items-start gap-14 py-6 pb-14">
        <!-- Gallery -->
        <div>
          <div class="aspect-[4/5] overflow-hidden rounded-lg2 bg-cardbg">
            <img :src="currentVariant.img" :alt="product.name" class="h-full w-full object-cover" />
          </div>
          <div class="mt-3 flex gap-2.5">
            <button
              v-for="(v, i) in product.variants" :key="v.name"
              class="h-20 w-[66px] overflow-hidden rounded-card border-2 bg-cardbg"
              :style="{ borderColor: i === varIdx ? '#1C1A17' : 'transparent' }"
              @click="varIdx = i">
              <img :src="v.img" :alt="v.name" class="h-full w-full object-cover" />
            </button>
          </div>
        </div>

        <!-- Info -->
        <div class="pt-1.5">
          <span class="eyebrow">{{ product.type }}</span>
          <h1 class="h-display mt-3 text-[44px] leading-[1.06]">{{ product.name }}</h1>
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

          <!-- Size -->
          <div class="mt-6">
            <div class="mb-3 text-[12px] uppercase tracking-[0.14em] text-faint">Pilih Ukuran</div>
            <div class="flex flex-wrap gap-2.5">
              <button
                v-for="s in SIZES" :key="s"
                class="min-w-[54px] rounded-lg2 border px-4 py-2.5 text-[13.5px]"
                :class="size === s ? 'border-ink bg-ink text-canvas' : 'border-[#D9D2C7] text-[#3A372F]'"
                @click="size = s">{{ s }}</button>
            </div>
          </div>

          <!-- Qty + add -->
          <div class="mt-7 flex items-center gap-4">
            <div class="flex items-center gap-[18px] rounded-pill border border-[#D9D2C7] px-[18px] py-[9px]">
              <button class="w-4 text-[18px] leading-none" @click="qty = Math.max(1, qty - 1)">−</button>
              <span class="min-w-[14px] text-center text-[15px]">{{ qty }}</span>
              <button class="w-4 text-[18px] leading-none" @click="qty = Math.min(9, qty + 1)">+</button>
            </div>
            <button class="btn-primary flex-1 whitespace-nowrap" @click="addToCart">
              Tambah ke Keranjang · {{ rp(product.price * qty) }}
            </button>
          </div>

          <!-- Size table -->
          <div class="mt-[30px] overflow-hidden rounded-lg2 border border-line">
            <div class="grid grid-cols-4 bg-panel px-4 py-[11px] text-[11px] uppercase tracking-[0.08em] text-faint">
              <span>Ukuran</span><span>{{ product.sizeCols[0] }}</span><span>{{ product.sizeCols[1] }}</span><span>{{ product.sizeCols[2] }}</span>
            </div>
            <div v-for="row in product.sizes" :key="row[0]" class="grid grid-cols-4 border-t border-[#F0EBE2] px-4 py-2.5 text-[13.5px] text-[#3A372F]">
              <span class="font-medium text-ink">{{ row[0] }}</span><span>{{ row[1] }}</span><span>{{ row[2] }}</span><span>{{ row[3] }}</span>
            </div>
            <div class="border-t border-[#F0EBE2] px-4 py-2.5 text-[11.5px] text-faint">Ukuran dalam cm · selisih 1–2 cm karena pengukuran manual.</div>
          </div>
        </div>
      </section>

      <!-- Related -->
      <section v-if="related.length" class="container-nale pb-[90px] pt-2">
        <h3 class="h-display mb-6 text-[28px]">Mungkin cocok juga</h3>
        <div class="grid grid-cols-3 gap-[22px]">
          <ProductCard v-for="p in related" :key="p.id" :product="p" />
        </div>
      </section>
    </main>
  </div>
</template>
