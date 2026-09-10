import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useWhatsapp(text = 'Halo, saya mau tanya soal produk NALE', numberOverride = null) {
  const number = computed(() => numberOverride || usePage().props.whatsapp)
  const href = computed(() => (number.value ? `https://wa.me/${number.value}?text=${encodeURIComponent(text)}` : null))

  return { number, href }
}
