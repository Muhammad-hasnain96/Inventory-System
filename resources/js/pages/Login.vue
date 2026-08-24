<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md">
      <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Inventory System</h1>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="Enter your email"
            required
          />
          <span v-if="errors.email" class="text-red-500 text-sm">{{ errors.email }}</span>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            placeholder="Enter your password"
            required
          />
          <span v-if="errors.password" class="text-red-500 text-sm">{{ errors.password }}</span>
        </div>

        <div v-if="errors.general" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
          {{ errors.general }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition font-medium"
        >
          {{ loading ? 'Logging in...' : 'Login' }}
        </button>
      </form>

      <div class="mt-8 pt-8 border-t border-gray-200">
        <h3 class="font-semibold text-gray-700 mb-3">Demo Credentials:</h3>
        <div class="space-y-2 text-sm text-gray-600">
          <p><strong>Super Admin:</strong> admin@inventory.local / password123</p>
          <p><strong>Branch Manager:</strong> john@inventory.local / password123</p>
          <p><strong>Sales User:</strong> alice@inventory.local / password123</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../services/api'

const router = useRouter()
const form = ref({ email: '', password: '' })
const errors = ref({})
const loading = ref(false)

const handleLogin = async () => {
  errors.value = {}
  loading.value = true

  try {
    const response = await apiClient.post('/login', form.value)
    localStorage.setItem('token', response.data.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.data.user))
    localStorage.setItem('permissions', JSON.stringify(response.data.data.permissions))
    router.push('/app/dashboard')
  } catch (error) {
    if (error.response?.data?.message) {
      errors.value.general = error.response.data.message
    } else {
      errors.value.general = 'Login failed. Please try again.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
