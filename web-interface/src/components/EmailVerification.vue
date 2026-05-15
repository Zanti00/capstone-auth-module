<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 p-8 bg-white rounded-xl shadow-lg border border-gray-100">
      <div class="text-center">
        <div v-if="status === 'loading'" class="flex flex-col items-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
          <h2 class="text-2xl font-bold text-gray-900">Verifying your email...</h2>
        </div>

        <div v-if="status === 'success'" class="space-y-4">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 text-green-600">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900">Email Verified!</h2>
          <p class="text-gray-600">Your email has been successfully verified. You can now access all features.</p>
          <router-link to="/" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
            Go to Dashboard
          </router-link>
        </div>

        <div v-if="status === 'expired'" class="space-y-4">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 text-red-600">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900">Link Expired</h2>
          <p class="text-gray-600">This verification link has expired or is invalid.</p>
          <button 
            @click="resendVerification" 
            :disabled="resending"
            class="w-full px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200 disabled:opacity-50"
          >
            {{ resending ? 'Sending...' : 'Resend Verification Email' }}
          </button>
          <p v-if="resendMessage" :class="resendError ? 'text-red-500' : 'text-green-500'" class="text-sm mt-2">
            {{ resendMessage }}
          </p>
        </div>

        <div v-if="status === 'already-verified'" class="space-y-4">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 text-blue-600">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900">Already Verified</h2>
          <p class="text-gray-600">Your email is already verified. You're all set!</p>
          <router-link to="/" class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
            Go to Dashboard
          </router-link>
        </div>

        <div v-if="status === 'error'" class="space-y-4">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 text-red-600">
            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h2 class="text-3xl font-extrabold text-gray-900">Verification Failed</h2>
          <p class="text-gray-600">{{ errorMessage || 'An unexpected error occurred.' }}</p>
          <router-link to="/" class="inline-block px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition duration-200">
            Back to Home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const status = ref<'loading' | 'success' | 'expired' | 'already-verified' | 'error'>('loading')
const errorMessage = ref('')
const resending = ref(false)
const resendMessage = ref('')
const resendError = ref(false)

const verifyEmail = async () => {
  const token = route.query.token
  if (!token) {
    status.value = 'error'
    errorMessage.value = 'No verification token provided.'
    return
  }

  try {
    const response = await axios.get(`http://localhost:8000/api/verify-email?token=${token}`)
    if (response.data.message === 'Email already verified.') {
      status.value = 'already-verified'
    } else {
      status.value = 'success'
    }
  } catch (error: any) {
    if (error.response?.status === 400) {
      status.value = 'expired'
    } else {
      status.value = 'error'
      errorMessage.value = error.response?.data?.message || 'Verification failed.'
    }
  }
}

const resendVerification = async () => {
  resending.value = true
  resendMessage.value = ''
  resendError.value = false

  try {
    const token = localStorage.getItem('access_token')
    await axios.post('http://localhost:8000/api/send-verification', {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    resendMessage.value = 'Verification email sent successfully.'
  } catch (error: any) {
    resendError.value = true
    resendMessage.value = error.response?.data?.message || 'Failed to resend verification email.'
  } finally {
    resending.value = false
  }
}

onMounted(() => {
  verifyEmail()
})
</script>
