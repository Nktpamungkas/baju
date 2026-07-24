<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ password: '' })

function submit() {
  form.post('/admin/login', { onFinish: () => form.reset('password') })
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-panel px-6 font-sans text-ink">
    <div class="w-full max-w-[380px]">
      <div class="mb-8 flex flex-col items-center gap-1">
        <span class="font-display text-[30px] tracking-[0.16em]">NALE</span>
        <span class="text-[10px] uppercase tracking-[0.3em] text-faint">Admin Panel</span>
      </div>
      <form class="rounded-[14px] border border-line bg-canvas p-[28px]" @submit.prevent="submit">
        <label class="flex flex-col gap-2">
          <span class="text-[13px] text-muted">Password admin</span>
          <input v-model="form.password" type="password" autofocus
            class="rounded-lg2 border px-3.5 py-3 text-[14px]" style="border-color:#DDD5C9;background:#fff"
            placeholder="••••••••" />
        </label>
        <div v-if="form.errors.password" class="mt-2 text-[12.5px] text-accent">{{ form.errors.password }}</div>
        <button type="submit" :disabled="form.processing"
          class="btn-primary mt-5 w-full disabled:opacity-60">Masuk</button>
        <p class="mt-4 text-center text-[12px] text-faint">Default: <code>nale123</code> — ubah di <code>.env</code></p>
      </form>
    </div>
  </div>
</template>
