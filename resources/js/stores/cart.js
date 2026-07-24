import { defineStore } from 'pinia'

// Cart store. Persists to localStorage. Replace the dummy `checkout()` with a
// real call to your Laravel backend (Inertia router.post('/checkout', ...)).
export const useCart = defineStore('cart', {
  state: () => ({
    // line item shape: { id, name, variant, size, price, img, qty }
    items: JSON.parse(localStorage.getItem('nale_cart') || '[]'),
  }),
  getters: {
    count: (s) => s.items.reduce((a, b) => a + b.qty, 0),
    subtotal: (s) => s.items.reduce((a, b) => a + b.price * b.qty, 0),
    shipping() {
      const FREE_THRESHOLD = 250000
      return this.subtotal === 0 || this.subtotal >= FREE_THRESHOLD ? 0 : 15000
    },
    total() { return this.subtotal + this.shipping },
  },
  actions: {
    add(line) {
      const i = this.items.findIndex(
        (x) => x.id === line.id && x.variant === line.variant && x.size === line.size
      )
      if (i >= 0) this.items[i].qty += line.qty
      else this.items.push({ ...line })
      this.persist()
    },
    inc(i) { this.items[i].qty++; this.persist() },
    dec(i) {
      if (this.items[i].qty > 1) this.items[i].qty--
      else this.items.splice(i, 1)
      this.persist()
    },
    remove(i) { this.items.splice(i, 1); this.persist() },
    clear() { this.items = []; this.persist() },
    persist() { localStorage.setItem('nale_cart', JSON.stringify(this.items)) },
  },
})

export const rp = (n) => 'Rp ' + n.toLocaleString('id-ID')
