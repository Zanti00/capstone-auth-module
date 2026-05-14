<script setup lang="ts">
import { ref } from 'vue'
import api from '@/lib/api'
import { Loader2, ArrowLeft } from 'lucide-vue-next'

const email = ref('')
const isLoading = ref(false)
const message = ref('')
const error = ref('')

const handleSubmit = async () => {
  isLoading.value = true
  message.value = ''
  error.value = ''

  try {
    const response = await api.post('/api/forgot-password', { email: email.value })
    message.value = response.data.message || 'If an account exists with that email, we have sent a reset link.'
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Something went wrong. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 font-sans">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-10 transform transition-all">
      <div class="mb-8">
        <router-link to="/" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900 mb-6 transition-colors">
          <ArrowLeft class="mr-2 h-4 w-4" />
          Back to login
        </router-link>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Forgot password?</h1>
        <p class="text-slate-500 mt-2">Enter your email and we'll send you a reset link.</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="space-y-2">
          <label for="email" class="text-sm font-semibold text-slate-700">Email Address</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="name@example.com"
            class="flex h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
            required
          />
        </div>

        <div v-if="message" class="p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-sm font-medium text-emerald-700 animate-in fade-in slide-in-from-top-1">
          {{ message }}
        </div>

        <div v-if="error" class="p-4 rounded-xl bg-red-50 border border-red-100 text-sm font-medium text-red-700 animate-in fade-in slide-in-from-top-1">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="isLoading || !!message"
          class="w-full h-12 inline-flex items-center justify-center rounded-xl bg-slate-900 text-slate-50 font-semibold hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-slate-900/10"
        >
          <Loader2 v-if="isLoading" class="mr-2 h-5 w-5 animate-spin" />
          {{ isLoading ? 'Sending Link...' : 'Send Reset Link' }}
        </button>
      </form>
    </div>
  </div>
</template>
