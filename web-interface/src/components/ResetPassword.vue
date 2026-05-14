<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/lib/api'
import { Loader2, Eye, EyeOff, CheckCircle2 } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()

const form = reactive({
  token: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const showPassword = ref(false)
const isLoading = ref(false)
const isSuccess = ref(false)
const errors = ref<Record<string, string[]>>({})
const generalError = ref('')

onMounted(() => {
  form.token = route.query.token as string || ''
  form.email = route.query.email as string || ''
  
  if (!form.token || !form.email) {
    generalError.value = 'Invalid or missing reset token. Please request a new link.'
  }
})

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleSubmit = async () => {
  isLoading.value = true
  errors.value = {}
  generalError.value = ''

  try {
    const response = await api.post('/api/reset-password', form)
    isSuccess.value = true
    setTimeout(() => {
      router.push('/')
    }, 3000)
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else {
      generalError.value = error.response?.data?.message || 'Failed to reset password. The link may have expired.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 px-4 font-sans">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 p-10 transform transition-all">
      
      <div v-if="isSuccess" class="text-center animate-in zoom-in duration-300">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-100 mb-6">
          <CheckCircle2 class="h-8 w-8 text-emerald-600" />
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Password reset!</h1>
        <p class="text-slate-500 mt-2">Your password has been updated. Redirecting to login...</p>
      </div>

      <div v-else>
        <div class="mb-8 text-center">
          <h1 class="text-3xl font-bold tracking-tight text-slate-900">Set new password</h1>
          <p class="text-slate-500 mt-2">Please choose a strong password to secure your account.</p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-5">
          <!-- Password Field -->
          <div class="space-y-2">
            <label for="password" class="text-sm font-semibold text-slate-700">New Password</label>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="flex h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900 pr-12"
                :class="{ 'border-red-500': errors.password }"
                required
              />
              <button
                type="button"
                @click="togglePassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
              >
                <Eye v-if="!showPassword" :size="20" />
                <EyeOff v-else :size="20" />
              </button>
            </div>
            <p v-if="errors.password" class="text-xs font-medium text-red-500 ml-1">
              {{ errors.password[0] }}
            </p>
          </div>

          <!-- Confirm Password Field -->
          <div class="space-y-2">
            <label for="password_confirmation" class="text-sm font-semibold text-slate-700">Confirm New Password</label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="••••••••"
              class="flex h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm transition-all focus:outline-none focus:ring-2 focus:ring-slate-900/10 focus:border-slate-900"
              required
            />
          </div>

          <div v-if="generalError" class="p-4 rounded-xl bg-red-50 border border-red-100 text-sm font-medium text-red-700">
            {{ generalError }}
          </div>

          <button
            type="submit"
            :disabled="isLoading || !!generalError"
            class="w-full h-12 inline-flex items-center justify-center rounded-xl bg-slate-900 text-slate-50 font-semibold hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 disabled:opacity-50 transition-all shadow-lg shadow-slate-900/10 mt-2"
          >
            <Loader2 v-if="isLoading" class="mr-2 h-5 w-5 animate-spin" />
            {{ isLoading ? 'Updating Password...' : 'Reset Password' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
