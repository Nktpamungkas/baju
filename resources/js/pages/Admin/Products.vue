<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/components/AdminLayout.vue'
import { rp } from '@/lib/format'

const props = defineProps({ products: { type: Array, required: true } })

const TYPES = ['Setelan', 'Dress', 'Atasan', 'Celana']
const modal = ref(false)
const draft = ref(null)
const isNew = ref(false)

function blank() {
  return {
    id: null, name: '', type: 'Setelan', price: 89000, word: 'warna',
    material: '', desc: '', shopee: '', toko: '', variants: [],
    sizeCols: ['Dada', 'Panjang', 'Lengan'], sizes: [['S', 0, 0, 0], ['M', 0, 0, 0], ['L', 0, 0, 0]],
  }
}
function openAdd() { draft.value = blank(); isNew.value = true; modal.value = true }
function openEdit(p) { draft.value = JSON.parse(JSON.stringify(p)); isNew.value = false; modal.value = true }
function close() { modal.value = false; draft.value = null }

function addVariant() { draft.value.variants.push({ name: '', img: '' }) }
function removeVariant(i) { draft.value.variants.splice(i, 1) }

async function uploadVariant(i, e) {
  const file = e.target.files && e.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('photo', file)
  try {
    const { data } = await axios.post('/admin/upload', fd)
    draft.value.variants[i].img = data.url
  } catch (err) {
    alert('Gagal unggah foto. Pastikan file gambar < 10MB.')
  }
}

function save() {
  const d = draft.value
  const payload = { ...d, price: Number(d.price) || 0 }
  const opts = { onSuccess: close, preserveScroll: true }
  if (isNew.value) router.post('/admin/produk', payload, opts)
  else router.put(`/admin/produk/${d.id}`, payload, opts)
}

function destroy(p) {
  if (confirm(`Hapus produk "${p.name}"?`)) {
    router.delete(`/admin/produk/${p.id}`, { preserveScroll: true })
  }
}

const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
</script>

<template>
  <AdminLayout active="products">
    <header class="sticky top-0 z-20 flex items-center justify-between border-b px-[34px] py-5" style="background:rgba(243,239,232,.85);backdrop-filter:blur(12px);border-color:#E2DBCF">
      <div>
        <h1 class="m-0 font-display text-[28px] font-light leading-none">Produk</h1>
        <span class="text-[13px] text-faint">{{ products.length }} produk aktif · pesanan via marketplace</span>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-[12.5px] text-faint">{{ today }}</span>
        <button class="flex items-center gap-[7px] rounded-pill bg-ink px-[18px] py-2.5 text-[13.5px] font-medium text-panel" @click="openAdd">+ Tambah Produk</button>
      </div>
    </header>

    <div class="px-[34px] pb-[60px] pt-[30px]">
      <div class="overflow-hidden rounded-[12px] border bg-canvas" style="border-color:#E9E3D9">
        <div class="grid gap-4 bg-panel px-[22px] py-3.5 text-[11.5px] uppercase tracking-[0.06em] text-faint" style="grid-template-columns:2.4fr .9fr .9fr 1fr 1.1fr 1fr">
          <span>Produk</span><span>Kategori</span><span>Harga</span><span>Varian</span><span>Marketplace</span><span class="text-right">Aksi</span>
        </div>
        <div v-for="p in products" :key="p.id" class="grid items-center gap-4 border-t px-[22px] py-3.5" style="grid-template-columns:2.4fr .9fr .9fr 1fr 1.1fr 1fr;border-color:#F0EBE2">
          <div class="flex min-w-0 items-center gap-[13px]">
            <img :src="(p.variants[0] || {}).img" :alt="p.name" class="h-[54px] w-[44px] rounded-card bg-cardbg object-cover" />
            <div class="min-w-0">
              <div class="text-[14.5px]">{{ p.name }}</div>
              <div class="truncate text-[12px] text-faint">{{ p.material }}</div>
            </div>
          </div>
          <span class="text-[13.5px] text-muted">{{ p.type }}</span>
          <span class="text-[13.5px]">{{ rp(p.price) }}</span>
          <div class="flex gap-[5px]">
            <span v-for="(w, i) in p.variants.slice(0, 4)" :key="i" :title="w.name" class="h-4 w-4 overflow-hidden rounded-pill border" style="border-color:#E2DBCF">
              <img :src="w.img" class="h-full w-full object-cover" />
            </span>
          </div>
          <div class="flex flex-wrap gap-1.5">
            <a :href="p.shopee || '#'" :target="p.shopee ? '_blank' : '_self'" rel="noopener"
              class="rounded-pill border px-2.5 py-1 text-[11.5px] no-underline"
              :style="p.shopee ? 'background:#FBFAF8;color:#1C1A17;border-color:#D9D2C7' : 'color:#C4BCAE;border-color:#EBE5DB'">Shopee</a>
            <a :href="p.toko || '#'" :target="p.toko ? '_blank' : '_self'" rel="noopener"
              class="rounded-pill border px-2.5 py-1 text-[11.5px] no-underline"
              :style="p.toko ? 'background:#FBFAF8;color:#1C1A17;border-color:#D9D2C7' : 'color:#C4BCAE;border-color:#EBE5DB'">Tokopedia</a>
          </div>
          <div class="flex justify-end gap-2">
            <button class="rounded-pill border px-3.5 py-[7px] text-[12.5px]" style="border-color:#D9D2C7" @click="openEdit(p)">Edit</button>
            <button class="rounded-pill border px-[11px] py-[7px] text-[12.5px]" style="border-color:#E7CFC7;color:#B5675F" @click="destroy(p)">Hapus</button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL -->
    <div v-if="modal" class="fixed inset-0 z-[60] flex items-start justify-center overflow-auto px-5 py-11" style="background:rgba(28,26,23,.42);backdrop-filter:blur(3px)">
      <div class="w-full max-w-[620px] rounded-[14px] bg-canvas" style="box-shadow:0 24px 60px rgba(28,26,23,.28)">
        <div class="flex items-center justify-between border-b px-[26px] py-5" style="border-color:#EFE9DE">
          <h2 class="m-0 font-display text-[21px] font-normal">{{ isNew ? 'Tambah Produk' : 'Edit Produk' }}</h2>
          <button class="text-[22px] leading-none text-faint" @click="close">×</button>
        </div>

        <div class="flex flex-col gap-4 px-[26px] py-6">
          <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Nama Produk</span>
            <input v-model="draft.name" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
          <div class="grid grid-cols-2 gap-3.5">
            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Kategori</span>
              <select v-model="draft.type" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff">
                <option v-for="t in TYPES" :key="t" :value="t">{{ t }}</option>
              </select></label>
            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Harga (Rp)</span>
              <input v-model="draft.price" type="number" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
          </div>
          <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Material</span>
            <input v-model="draft.material" class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
          <div class="grid grid-cols-2 gap-3.5">
            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Link Shopee</span>
              <input v-model="draft.shopee" placeholder="https://shopee.co.id/..." class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
            <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Link Tokopedia</span>
              <input v-model="draft.toko" placeholder="https://tokopedia.com/..." class="rounded-lg2 border px-3.5 py-2.5 text-[14px]" style="border-color:#DDD5C9;background:#fff" /></label>
          </div>
          <label class="flex flex-col gap-1.5"><span class="text-[12.5px] text-muted">Deskripsi</span>
            <textarea v-model="draft.desc" rows="3" class="resize-y rounded-lg2 border px-3.5 py-2.5 text-[14px] leading-[1.5]" style="border-color:#DDD5C9;background:#fff"></textarea></label>

          <div class="flex flex-col gap-2.5">
            <div class="flex items-center justify-between">
              <span class="text-[12.5px] text-muted">Varian &amp; Foto ({{ draft.variants.length }})</span>
              <button class="text-[12.5px] font-medium" style="color:var(--accent)" @click="addVariant">+ Tambah varian</button>
            </div>
            <div class="flex flex-col gap-2.5">
              <div v-for="(v, i) in draft.variants" :key="i" class="flex items-center gap-[11px] rounded-[10px] border p-2" style="background:#F6F2EB;border-color:#EBE5DB">
                <label class="relative flex h-14 w-[46px] flex-shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-card bg-cardbg">
                  <img v-if="v.img" :src="v.img" class="h-full w-full object-cover" />
                  <span v-else class="text-center text-[10px] leading-[1.3] text-faint">Foto</span>
                  <input type="file" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0" @change="uploadVariant(i, $event)" />
                </label>
                <input v-model="v.name" placeholder="Nama warna / motif" class="flex-1 rounded-lg2 border px-3 py-2 text-[13.5px]" style="border-color:#DDD5C9;background:#fff" />
                <button class="h-[30px] w-[30px] flex-shrink-0 rounded-pill border text-[16px] leading-none" style="border-color:#E7CFC7;color:#B5675F" @click="removeVariant(i)">×</button>
              </div>
            </div>
            <span class="text-[11.5px] text-faint">Klik kotak foto untuk unggah gambar tiap varian. Foto pertama jadi thumbnail produk.</span>
          </div>
        </div>

        <div class="flex justify-end gap-2.5 border-t px-[26px] py-[18px]" style="border-color:#EFE9DE">
          <button class="rounded-pill border px-5 py-2.5 text-[14px] text-muted" style="border-color:#D9D2C7" @click="close">Batal</button>
          <button class="rounded-pill bg-ink px-6 py-2.5 text-[14px] font-medium text-panel" @click="save">Simpan</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
