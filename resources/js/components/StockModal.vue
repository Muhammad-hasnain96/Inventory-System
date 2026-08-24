<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
      <h2 class="text-xl font-bold mb-4">
        {{ mode === 'add' ? '➕ Add Stock' : '🔄 Adjust Stock' }}
      </h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
          <p class="text-gray-900 font-medium">{{ inventory.product?.name }}</p>
          <p class="text-gray-500 text-sm">SKU: {{ inventory.product?.sku }}</p>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p class="text-sm text-gray-600">Current Stock: <span class="font-bold text-lg text-blue-600">{{ inventory.quantity }}</span></p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ mode === 'add' ? 'Quantity to Add' : 'Quantity Change' }} *
          </label>
          <input
            v-model.number="form.quantity"
            type="number"
            :min="mode === 'add' ? 1 : -inventory.quantity + 1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            required
          />
          <p v-if="mode === 'adjust'" class="text-sm text-gray-500 mt-1">
            Use negative numbers to decrease stock
          </p>
        </div>

        <div v-if="mode === 'adjust'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Adjustment *</label>
          <textarea
            v-model="form.notes"
            rows="2"
            placeholder="e.g., Inventory count correction, damage, theft, etc."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            required
          ></textarea>
        </div>

        <div v-if="mode === 'add'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
          <input
            v-model="form.notes"
            type="text"
            placeholder="e.g., Supplier delivery"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
          />
        </div>

        <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-3 py-2 rounded text-sm">
          {{ error }}
        </div>

        <div class="flex gap-2 pt-4">
          <button
            type="submit"
            :disabled="loading"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition"
          >
            {{ loading ? 'Processing...' : 'Confirm' }}
          </button>
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import apiClient from '../services/api'

const props = defineProps({
  inventory: Object,
  mode: String
})

const emit = defineEmits(['close', 'save'])

const form = ref({
  quantity: '',
  notes: ''
})

const loading = ref(false)
const error = ref('')

const handleSubmit = async () => {
  error.value = ''
  loading.value = true

  try {
    const endpoint = props.mode === 'add' ? 'add-stock' : 'adjust-stock'
    await apiClient.post(
      `/inventory/${props.inventory.id}/${endpoint}`,
      form.value
    )
    emit('save')
  } catch (err) {
    error.value = err.response?.data?.message || 'Operation failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
