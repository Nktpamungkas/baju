<script setup>
import { useWhatsapp } from '@/lib/whatsapp'

const props = defineProps({ text: { type: String, default: 'Halo, saya mau tanya soal produk NALE' } })
const { href } = useWhatsapp(props.text)
</script>

<template>
  <a
    v-if="href"
    :href="href" target="_blank" rel="noopener"
    class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] shadow-lg transition hover:scale-105"
    aria-label="Tanya via WhatsApp">
    <svg viewBox="0 0 32 32" class="h-7 w-7 fill-white">
      <path d="M16.004 3C9.376 3 4 8.373 4 15c0 2.34.687 4.523 1.875 6.36L4 29l7.86-1.828A11.94 11.94 0 0 0 16.004 27C22.63 27 28 21.627 28 15S22.63 3 16.004 3Zm6.99 17.1c-.298.836-1.47 1.53-2.036 1.618-.55.086-1.155.316-3.87-.808-3.253-1.34-5.34-4.64-5.5-4.856-.16-.216-1.31-1.744-1.31-3.328 0-1.584.83-2.362 1.124-2.686.294-.324.64-.405.854-.405.213 0 .427.002.613.011.196.01.46-.074.72.55.267.64.906 2.212.986 2.373.08.16.134.35.027.56-.107.213-.16.345-.32.53-.16.187-.334.418-.478.562-.16.16-.327.334-.14.66.187.324.833 1.376 1.788 2.228 1.227 1.093 2.26 1.432 2.586 1.592.324.16.51.134.7-.08.187-.213.803-.936 1.017-1.257.213-.324.427-.267.72-.16.294.107 1.858.876 2.178 1.036.32.16.533.24.612.373.08.134.08.774-.218 1.61Z" />
    </svg>
  </a>
</template>
