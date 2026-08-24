<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto p-4">
    <form @submit.prevent="handleSubmit" class="bg-white rounded-xl shadow-2xl max-w-2xl w-full my-8 flex flex-col max-h-[90vh]">
      <div class="sticky top-0 bg-white border-b border-gray-200 p-6 -m-0">
        <h2 class="text-2xl font-bold text-gray-900">{{ product ? '✏️ Edit Product' : '➕ Add Product' }}</h2>
      </div>

      <div class="space-y-4 flex-1 overflow-y-auto p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Product Name *</label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g., Product Name"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">SKU *</label>
            <input
              v-model="form.sku"
              type="text"
              placeholder="e.g., SKU-001"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Cost Price ($) *</label>
            <input
              v-model.number="form.cost_price"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Sale Price ($) *</label>
            <input
              v-model.number="form.sale_price"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Tax (%) *</label>
            <input
              v-model.number="form.tax_percentage"
              type="number"
              step="0.01"
              min="0"
              max="100"
              placeholder="10"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
              required
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-800 mb-2">Status *</label>
            <select
              v-model="form.status"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition bg-white"
              required
            >
              <option value="active">✓ Active</option>
              <option value="inactive">✗ Inactive</option>
            </select>
          </div>
          <div></div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-800 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            placeholder="Add product description..."
            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition resize-none"
          ></textarea>
        </div>

        <div v-if="error" class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
          ⚠️ {{ error }}
        </div>
      </div>

      <div class="sticky bottom-0 bg-white border-t border-gray-200 p-6 flex gap-3">
        <button
          type="submit"
          :disabled="loading"
          class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
        >
          {{ loading ? '⏳ Saving...' : '💾 Save' }}
        </button>
        <button
          type="button"
          @click="$emit('close')"
          class="flex-1 px-4 py-2.5 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
        >
          Cancel
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import apiClient from '../services/api'

const props = defineProps({ product: Object })
const emit = defineEmits(['close', 'save'])

const form = ref({
  name: props.product?.name || '',
  sku: props.product?.sku || '',
  description: props.product?.description || '',
  cost_price: props.product?.cost_price || '',
  sale_price: props.product?.sale_price || '',
  tax_percentage: props.product?.tax_percentage || 10,
  status: props.product?.status || 'active'
})

const loading = ref(false)
const error = ref('')

const handleSubmit = async () => {
  error.value = ''
  loading.value = true

  try {
    if (props.product?.id) {
      await apiClient.put(`/products/${props.product.id}`, form.value)
    } else {
      await apiClient.post('/products', form.value)
    }
    emit('save')
  } catch (err) {
    error.value = err.response?.data?.message || 'Operation failed'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped></style>
