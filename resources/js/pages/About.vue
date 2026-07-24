<script setup>
import { router } from '@inertiajs/vue3'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'

const values = [
  { t: 'Bahan pilihan', d: 'Katun organik, linen, dan muslin yang adem dan lembut di kulit anak.' },
  { t: 'Pewarna aman', d: 'Tinta tersertifikasi Oeko-Tex Standard 100 — aman untuk bayi & anak.' },
  { t: 'Jahitan rapi', d: 'Dikerjakan teliti agar tahan dipakai untuk gerak aktif sehari-hari.' },
]
</script>

<template>
  <div class="min-h-screen bg-canvas font-sans text-ink">
    <AppHeader active="about" />

    <main>
      <section class="container-nale grid grid-cols-[1.05fr_0.95fr] items-center gap-14 py-16">
        <div>
          <span class="eyebrow">Tentang NALE</span>
          <h1 class="h-display mt-[18px] text-[54px] leading-[1.05] tracking-[-0.01em]">
            Dibuat <em class="italic text-accent">tenang</em>, untuk tumbuh bersama.
          </h1>
          <p class="mt-6 max-w-[440px] text-[17px] leading-[1.6] text-muted">
            NALE lahir dari keinginan sederhana: pakaian anak yang nyaman, berbahan baik,
            dan berwarna tenang — bukan sekadar ramai. Kami memilih material yang adem,
            pewarna yang aman, dan jahitan yang rapi supaya setiap potong bisa dipakai lama.
          </p>
          <div class="mt-[34px] flex gap-3.5">
            <button class="btn-primary" @click="router.visit('/katalog')">Lihat Koleksi</button>
          </div>
        </div>
        <div class="grid grid-cols-2 grid-rows-2 gap-2.5">
          <img src="/img/koko-olivegreen.jpg" alt="" class="aspect-square w-full rounded-lg2 object-cover" />
          <img src="/img/tracy-operamauve.jpg" alt="" class="aspect-square w-full rounded-lg2 object-cover" />
          <img src="/img/orion-denim.jpg" alt="" class="aspect-square w-full rounded-lg2 object-cover" />
          <img src="/img/irina-rosewood.jpg" alt="" class="aspect-square w-full rounded-lg2 object-cover" />
        </div>
      </section>

      <div class="border-y border-line bg-panel">
        <div class="container-nale grid grid-cols-3 gap-10 py-14">
          <div v-for="v in values" :key="v.t">
            <div class="font-display text-[22px] text-ink">{{ v.t }}</div>
            <p class="mt-2.5 text-[14.5px] leading-[1.6] text-muted">{{ v.d }}</p>
          </div>
        </div>
      </div>

      <section class="container-nale py-[72px] text-center">
        <span class="eyebrow">Filosofi</span>
        <p class="mx-auto mt-5 max-w-[680px] font-display text-[28px] font-light leading-[1.5] text-ink">
          Anak tumbuh cepat. Pakaian mereka sebaiknya menemani masa itu dengan nyaman,
          bukan menjadi beban — sederhana, bersih, dan menyenangkan dipakai.
        </p>
      </section>
    </main>

    <AppFooter />
  </div>
</template>
