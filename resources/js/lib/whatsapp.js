import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useWhatsapp(text = 'Halo, saya mau tanya soal produk NALE') {
  const number = computed(() => usePage().props.whatsapp)
  const href = computed(() => (number.value ? `https://wa.me/${number.value}?text=${encodeURIComponent(text)}` : null))

  return { number, href }
}
