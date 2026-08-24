<template>
  <div class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-4xl font-bold text-gray-800 mb-2">Recent Orders</h1>
      <p class="text-gray-600">View all recent orders in your inventory system</p>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Search -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by order number or customer..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <select
            v-model="selectedStatus"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Sort -->
        <div>
          <select
            v-model="sortBy"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="recent">Most Recent</option>
            <option value="oldest">Oldest First</option>
            <option value="highest">Highest Amount</option>
            <option value="lowest">Lowest Amount</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12 text-gray-500">
      <p class="text-lg">Loading orders...</p>
    </div>

    <!-- Orders Table -->
    <template v-else>
      <div v-if="filteredOrders.length" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Order #</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Created By</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Items</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Total</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Status</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Created At</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4 font-bold text-blue-600">{{ order.order_number }}</td>
              <td class="px-6 py-4 text-gray-600">{{ order.created_by?.name || 'N/A' }}</td>
              <td class="px-6 py-4 text-center font-medium text-gray-700">{{ order.items?.length ?? 0 }}</td>
              <td class="px-6 py-4 text-right font-bold text-gray-900">${{ parseFloat(order.total_amount).toFixed(2) }}</td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="{
                    'bg-blue-100 text-blue-800': order.status === 'pending',
                    'bg-green-100 text-green-800': order.status === 'confirmed',
                    'bg-gray-100 text-gray-800': order.status === 'completed',
                    'bg-red-100 text-red-800': order.status === 'cancelled'
                  }"
                  class="px-3 py-1 rounded-full text-sm font-medium"
                >
                  {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
              <td class="px-6 py-4 text-center">
                <button
                  @click="viewOrder(order)"
                  class="text-blue-600 hover:text-blue-800 font-medium text-sm mr-3"
                >
                  View
                </button>
                <button
                  v-if="order.status !== 'cancelled'"
                  @click="cancelOrder(order.id)"
                  class="text-red-600 hover:text-red-800 font-medium text-sm"
                >
                  Cancel
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="bg-white border-t px-6 py-4 flex items-center justify-between">
          <div class="text-sm text-gray-600">
            Showing {{ filteredOrders.length }} of {{ allOrders.length }} orders
          </div>
          <div class="flex gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage === 1"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="currentPage = currentPage + 1"
              :disabled="currentPage * itemsPerPage >= filteredOrders.length"
              class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg shadow p-12 text-center">
        <div class="text-6xl mb-4">📭</div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">No Orders Found</h2>
        <p class="text-gray-600 mb-6">
          {{ searchQuery || selectedStatus ? 'Try adjusting your filters' : 'No orders have been created yet' }}
        </p>
        <router-link
          v-if="!searchQuery && !selectedStatus"
          to="/app/orders"
          class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          Create New Order
        </router-link>
      </div>
    </template>

    <!-- Order Detail Modal -->
    <OrderDetailModal
      v-if="showDetailModal"
      :order="selectedOrder"
      @close="showDetailModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'
import OrderDetailModal from '../components/OrderDetailModal.vue'

const allOrders = ref([])
const loading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref('')
const sortBy = ref('recent')
const currentPage = ref(1)
const itemsPerPage = ref(15)
const showDetailModal = ref(false)
const selectedOrder = ref(null)

const filteredOrders = computed(() => {
  let filtered = allOrders.value

  // Search
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(order =>
      order.order_number.toLowerCase().includes(query) ||
      order.created_by?.name.toLowerCase().includes(query)
    )
  }

  // Status filter
  if (selectedStatus.value) {
    filtered = filtered.filter(order => order.status === selectedStatus.value)
  }

  // Sort
  if (sortBy.value === 'oldest') {
    filtered = filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at))
  } else if (sortBy.value === 'highest') {
    filtered = filtered.sort((a, b) => parseFloat(b.total_amount) - parseFloat(a.total_amount))
  } else if (sortBy.value === 'lowest') {
    filtered = filtered.sort((a, b) => parseFloat(a.total_amount) - parseFloat(b.total_amount))
  } else {
    // recent (default)
    filtered = filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
  }

  return filtered
})

const loadOrders = async () => {
  try {
    loading.value = true
    const response = await apiClient.get('/orders')
    allOrders.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const viewOrder = (order) => {
  selectedOrder.value = order
  showDetailModal.value = true
}

const cancelOrder = async (id) => {
  if (confirm('Are you sure you want to cancel this order?')) {
    try {
      await apiClient.post(`/orders/${id}/cancel`, { reason: 'Cancelled by admin' })
      await loadOrders()
    } catch (error) {
      console.error('Failed to cancel order:', error)
      alert('Failed to cancel order')
    }
  }
}

onMounted(() => {
  loadOrders()
})
</script>

<style scoped></style>
