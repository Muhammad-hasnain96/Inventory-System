<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto py-8">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl my-auto">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 sticky top-0 bg-white">
        <h2 class="text-2xl font-bold">🛒 Create Order</h2>
      </div>

      <!-- Main Content -->
      <div class="overflow-y-auto" style="max-height: calc(100vh - 200px)">
        <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
          <!-- Branch Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Branch</label>
            <select
              v-model="selectedBranchId"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"
              required
            >
              <option value="">-- Select a branch --</option>
              <option v-for="branch in availableBranches" :key="branch.id" :value="branch.id">
                {{ branch.name }} ({{ branch.code }})
              </option>
            </select>
          </div>

          <!-- Product Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Add Products to Order</label>
            <div class="flex gap-2">
              <select
                v-model="selectedProductId"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                @change="addProduct"
              >
                <option value="">-- Select a product --</option>
                <option v-for="product in availableProducts" :key="product.id" :value="product.id">
                  {{ product.name }} - ${{ parseFloat(product.sale_price).toFixed(2) }} ({{ product.inventories.reduce((sum, inv) => sum + inv.quantity, 0) }} in stock)
                </option>
              </select>
              <button
                type="button"
                @click="addProduct"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
              >
                + Add
              </button>
            </div>
          </div>

          <!-- Order Items Table -->
          <div v-if="items.length" class="border rounded-lg overflow-hidden bg-white">
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b sticky top-0">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 whitespace-nowrap">Product</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 whitespace-nowrap">Price</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">Quantity</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 whitespace-nowrap">Total</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ item.product?.name }}</td>
                    <td class="px-4 py-3 text-right text-gray-600">${{ parseFloat(item.product?.sale_price).toFixed(2) }}</td>
                    <td class="px-4 py-3 text-center">
                      <input
                        v-model.number="item.quantity"
                        type="number"
                        min="1"
                        max="9999"
                        class="w-20 px-2 py-2 border border-gray-300 rounded text-center focus:ring-2 focus:ring-blue-500 outline-none"
                        @input="updateTotal"
                      />
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ (item.product?.sale_price * item.quantity).toFixed(2) }}</td>
                    <td class="px-4 py-3 text-center">
                      <button
                        type="button"
                        @click="removeItem(idx)"
                        class="inline-flex items-center justify-center w-7 h-7 text-red-600 hover:bg-red-50 rounded transition"
                        title="Remove item"
                      >
                        ✕
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Totals Section -->
            <div class="bg-gray-50 border-t px-4 py-4 space-y-2">
              <div class="flex justify-between items-center py-1">
                <span class="text-gray-600 font-medium">Subtotal:</span>
                <span class="text-lg font-semibold text-gray-900">${{ subtotal.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between items-center py-1">
                <span class="text-gray-600 font-medium">Tax (10%):</span>
                <span class="text-lg font-semibold text-gray-900">${{ taxAmount.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-t-2 border-gray-300 pt-3">
                <span class="text-gray-900 font-bold text-lg">Total:</span>
                <span class="text-2xl font-bold text-blue-600">${{ (subtotal + taxAmount).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-700 text-center">
            <p class="font-medium">📦 No products added yet</p>
            <p class="text-blue-600 mt-1">Select a product above to add it to your order</p>
          </div>

          <!-- Notes Section -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Order Notes (Optional)</label>
            <textarea
              v-model="notes"
              rows="3"
              placeholder="Add any special instructions or notes for this order..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm resize-none"
            ></textarea>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <p class="font-medium">⚠️ Error</p>
            <p>{{ error }}</p>
          </div>
        </form>
      </div>

      <!-- Footer (Sticky) -->
      <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 sticky bottom-0 flex gap-2">
        <button
          @click="handleSubmit"
          :disabled="loading || !items.length"
          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition font-medium text-sm"
        >
          {{ loading ? '⏳ Creating Order...' : '✓ Create Order' }}
        </button>
        <button
          type="button"
          @click="$emit('close')"
          class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition font-medium text-sm"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'

const emit = defineEmits(['close', 'save'])

const products = ref([])
const branches = ref([])
const items = ref([])
const selectedProductId = ref('')
const selectedBranchId = ref('')
const notes = ref('')
const loading = ref(false)
const error = ref('')

const availableProducts = computed(() => {
  return products.value
    .filter(product => {
      return product.inventories && product.inventories.some(inv => inv.quantity > 0)
    })
    .sort((a, b) => a.name.localeCompare(b.name))
})

const availableBranches = computed(() => {
  return branches.value.sort((a, b) => a.name.localeCompare(b.name))
})

const subtotal = computed(() => {
  return items.value.reduce((sum, item) => {
    return sum + (parseFloat(item.product.sale_price) * item.quantity)
  }, 0)
})

const taxAmount = computed(() => {
  return items.value.reduce((sum, item) => {
    const lineTotal = parseFloat(item.product.sale_price) * item.quantity
    const itemTax = lineTotal * (parseFloat(item.product.tax_percentage || 0) / 100)
    return sum + itemTax
  }, 0)
})

const addProduct = () => {
  if (!selectedProductId.value) return

  const product = products.value.find(p => p.id == selectedProductId.value)
  if (!product) {
    error.value = 'Product not found'
    return
  }

  const existingItem = items.value.find(i => i.product_id === product.id)

  if (existingItem) {
    existingItem.quantity += 1
  } else {
    items.value.push({
      product_id: product.id,
      product: { ...product },
      quantity: 1
    })
  }

  selectedProductId.value = ''
  error.value = ''
}

const removeItem = (idx) => {
  items.value.splice(idx, 1)
}

const updateTotal = () => {
  // Trigger reactive update
}

const handleSubmit = async () => {
  if (!selectedBranchId.value) {
    error.value = 'Please select a branch'
    return
  }

  if (items.value.length === 0) {
    error.value = 'Please add at least one product to the order'
    return
  }

  error.value = ''
  loading.value = true

  try {
    const orderData = {
      branch_id: parseInt(selectedBranchId.value),
      items: items.value.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity
      })),
      notes: notes.value || null
    }

    const response = await apiClient.post('/orders', orderData)
    
    // Reset form after successful save
    items.value = []
    notes.value = ''
    selectedProductId.value = ''
    selectedBranchId.value = ''
    error.value = ''
    
    emit('save')
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create order. Please try again.'
    console.error('Order creation error:', err)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    // Load branches
    const branchesResponse = await apiClient.get('/branches')
    branches.value = branchesResponse.data.data || []

    // Load products
    const productsResponse = await apiClient.get('/products?include_inventory=true&per_page=500')
    products.value = productsResponse.data.data || []
  } catch (err) {
    console.error('Failed to load data:', err)
    error.value = 'Failed to load products and branches. Please refresh the page.'
  }
})
</script>

<style scoped>
/* Custom scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
