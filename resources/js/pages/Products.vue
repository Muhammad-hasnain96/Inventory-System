<template>
  <div class="p-8">
    <!-- Access Denied Message -->
    <div v-if="!canManageProducts" class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
      <h2 class="text-2xl font-bold text-red-700 mb-2">Access Denied</h2>
      <p class="text-red-600">You don't have permission to manage products. This feature is only available to Administrators.</p>
    </div>

    <template v-else>
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Products</h1>
        <button
          @click="showCreateModal = true"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          ➕ Add Product
        </button>
      </div>

      <!-- Search Bar -->
      <div class="mb-6">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search products by name or SKU..."
          class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
        />
      </div>

      <!-- Products Table -->
      <div v-if="loading" class="text-center text-gray-500">Loading...</div>

      <div v-else-if="products.length" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Name</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">SKU</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Sale Price</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Tax %</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Status</th>
              <th v-if="canManageProducts" class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-medium text-gray-900">{{ product.name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ product.sku }}</td>
              <td class="px-6 py-4 text-right text-gray-900 font-medium">${{ parseFloat(product.sale_price).toFixed(2) }}</td>
              <td class="px-6 py-4 text-right text-gray-600">{{ product.tax_percentage }}%</td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="product.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  class="px-3 py-1 rounded-full text-sm font-medium"
                >
                  {{ product.status }}
                </span>
              </td>
              <td v-if="canManageProducts" class="px-6 py-4 text-center">
                <button
                  @click="editProduct(product)"
                  class="text-blue-600 hover:text-blue-800 mr-4 text-sm font-medium"
                >
                  Edit
                </button>
                <button
                  @click="deleteProduct(product.id)"
                  class="text-red-600 hover:text-red-800 text-sm font-medium"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        No products found. Start by adding a product.
      </div>

      <!-- Product Modal -->
      <ProductModal
        v-if="showCreateModal"
        :product="editingProduct"
        @close="showCreateModal = false"
        @save="handleSaveProduct"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import apiClient from '../services/api'
import ProductModal from '../components/ProductModal.vue'
import { usePermissions } from '../composables/usePermissions'

const { canManageProducts } = usePermissions()

const products = ref([])
const loading = ref(true)
const searchQuery = ref('')
const showCreateModal = ref(false)
const editingProduct = ref(null)

const filteredProducts = computed(() => {
  if (!searchQuery.value) return products.value
  const query = searchQuery.value.toLowerCase()
  return products.value.filter(
    p => p.name.toLowerCase().includes(query) || p.sku.toLowerCase().includes(query)
  )
})

const loadProducts = async () => {
  try {
    loading.value = true
    const params = searchQuery.value ? { q: searchQuery.value, per_page: 200 } : { per_page: 200 }
    const response = await apiClient.get('/products', { params })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to load products:', error)
  } finally {
    loading.value = false
  }
}

const editProduct = (product) => {
  editingProduct.value = product
  showCreateModal.value = true
}

const handleSaveProduct = async (data) => {
  await loadProducts()
  showCreateModal.value = false
  editingProduct.value = null
}

const deleteProduct = async (id) => {
  if (confirm('Are you sure you want to permanently delete this product? This action cannot be undone.')) {
    try {
      // Try force delete first (permanent deletion)
      await apiClient.delete(`/products/${id}/force`)
      await loadProducts()
    } catch (error) {
      // If force delete fails due to dependencies, show error message
      if (error.response?.data?.message) {
        alert(`Cannot delete product: ${error.response.data.message}`)
      } else {
        console.error('Failed to delete product:', error)
        alert('Failed to delete product. Please try again.')
      }
    }
  }
}

onMounted(() => loadProducts())
watch(() => searchQuery.value, () => loadProducts())
</script>

<style scoped></style>
