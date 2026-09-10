import { reactive, computed } from 'vue'

const STORAGE_KEY = 'nale_cart'

function load() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) return { discountCode: null, discountAmount: 0, shipping: null, ...JSON.parse(raw) }
  } catch {
    //
  }
  return { items: [], discountCode: null, discountAmount: 0, shipping: null }
}

const state = reactive(load())

function persist() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

// line: { id, name, variant, size, price, img, weight, qty }
function add(line) {
  const i = state.items.findIndex((x) => x.id === line.id && x.variant === line.variant && x.size === line.size)
  if (i >= 0) state.items[i].qty += line.qty
  else state.items.push({ ...line })
  persist()
}
function inc(i) { state.items[i].qty++; persist() }
function dec(i) {
  if (state.items[i].qty > 1) state.items[i].qty--
  else state.items.splice(i, 1)
  persist()
}
function remove(i) { state.items.splice(i, 1); persist() }
function clear() {
  state.items = []
  state.discountCode = null
  state.discountAmount = 0
  state.shipping = null
  persist()
}
function setShipping(option) { state.shipping = option; persist() }
function setDiscount(code, amount) { state.discountCode = code; state.discountAmount = amount; persist() }

const count = computed(() => state.items.reduce((a, b) => a + b.qty, 0))
const subtotal = computed(() => state.items.reduce((a, b) => a + b.price * b.qty, 0))
const shippingCost = computed(() => state.shipping?.price ?? 0)
const total = computed(() => Math.max(0, subtotal.value - state.discountAmount) + shippingCost.value)

export function useCart() {
  return { state, count, subtotal, shippingCost, total, add, inc, dec, remove, clear, setShipping, setDiscount }
}
