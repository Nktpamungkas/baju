<script setup>
// Ikon kecil dipakai di beberapa tempat (trust section beranda, footer) — sengaja
// inline SVG (bukan library ikon) biar tidak nambah dependency baru buat 4 ikon saja.
defineProps({ name: { type: String, required: true }, size: { type: [Number, String], default: 22 } })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path v-if="name === 'shield'" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
    <template v-else-if="name === 'truck'">
      <rect x="1" y="3" width="15" height="13" />
      <path d="M16 8h4l3 3v5h-7V8z" />
      <circle cx="5.5" cy="18.5" r="2.2" />
      <circle cx="18.5" cy="18.5" r="2.2" />
    </template>
    <path v-else-if="name === 'heart'" d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 000-7.8z" />
    <template v-else-if="name === 'pin'">
      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
      <circle cx="12" cy="10" r="3" />
    </template>
    <template v-else-if="name === 'instagram'">
      <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
      <path d="M16 11.4a4 4 0 11-3.4-3.4A4 4 0 0116 11.4z" />
      <circle cx="17.5" cy="6.5" r="0.6" fill="currentColor" stroke="none" />
    </template>
  </svg>
</template>
