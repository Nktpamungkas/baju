<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { rp } from '@/lib/format'

// Reusable product card — matches the catalog/home grid styling exactly.
const props = defineProps({ product: { type: Object, required: true } })
const isLowStock = computed(() => props.product.stock !== null && props.product.stock > 0 && props.product.stock <= 3)
const isOutOfStock = computed(() => props.product.stock === 0)
</script>

<template>
  <Link :href="`/produk/${product.id}`" class="group block cursor-pointer">
    <div class="relative aspect-[4/5] overflow-hidden rounded-card bg-cardbg">
      <img :src="product.variants[0].img" :alt="product.name" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
      <span v-if="isOutOfStock" class="absolute left-2 top-2 rounded-pill bg-ink/85 px-2.5 py-1 text-[10.5px] text-canvas">Stok Habis</span>
      <span v-else-if="isLowStock" class="absolute left-2 top-2 rounded-pill px-2.5 py-1 text-[10.5px] font-medium text-canvas" style="background:var(--accent)">Sisa {{ product.stock }}</span>
    </div>
    <div class="mt-3 flex items-baseline justify-between gap-2.5">
      <span class="text-[15px] text-ink">{{ product.name }}</span>
      <span class="whitespace-nowrap text-[14px] text-muted">{{ rp(product.price) }}</span>
    </div>
    <div class="mt-0.5 text-[12px] text-faint">
      {{ product.type }} · {{ product.variants.length }} {{ product.word }}
    </div>
  </Link>
</template>
