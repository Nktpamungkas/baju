<script setup>
import { Link } from '@inertiajs/vue3'
import { rp } from '@/lib/format'

// Reusable product card — matches the catalog/home grid styling exactly.
defineProps({ product: { type: Object, required: true } })
</script>

<template>
  <Link :href="`/produk/${product.id}`" class="block cursor-pointer">
    <div class="aspect-[4/5] overflow-hidden rounded-card bg-cardbg">
      <img :src="product.variants[0].img" :alt="product.name" loading="lazy" class="h-full w-full object-cover" />
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
