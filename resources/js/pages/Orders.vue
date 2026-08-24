<template>
  <div class="p-8">
    <!-- Access Denied Message -->
    <div v-if="!canCreateOrders" class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
      <h2 class="text-2xl font-bold text-red-700 mb-2">Access Denied</h2>
      <p class="text-red-600">You don't have permission to access orders. This feature is only available to Managers and Sales Users.</p>
    </div>

    <template v-else>
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Orders</h1>
        <button
          @click="showCreateModal = true"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          ➕ Create Order
        </button>
      </div>

      <div v-if="loading" class="text-center text-gray-500">Loading...</div>

      <template v-if="canViewOrders.value">
        <div v-if="orders.length" class="bg-white rounded-lg shadow overflow-hidden">
          <table class="w-full">
            <thead class="bg-gray-50 border-b">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Order #</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Created By</th>
                <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Items</th>
                <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Total</th>
                <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Status</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 font-bold text-blue-600">{{ order.order_number }}</td>
                <td class="px-6 py-4 text-gray-600">{{ order.created_by?.name }}</td>
                <td class="px-6 py-4 text-center font-medium">{{ order.items?.length ?? 0 }}</td>
                <td class="px-6 py-4 text-right font-bold">${{ parseFloat(order.total_amount).toFixed(2) }}</td>
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
                    {{ order.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                <td class="px-6 py-4 text-center">
                  <button
                    @click="viewOrder(order)"
                    class="text-blue-600 hover:text-blue-800 mr-3 text-sm font-medium"
                  >
                    View
                  </button>
                  <button
                    v-if="order.status !== 'cancelled' && canCancelOrders"
                    @click="cancelOrder(order.id)"
                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    Cancel
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
          No orders found. Start by creating a new order.
        </div>
      </template>

      <div v-else class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        <h2 class="text-xl font-semibold text-slate-900 mb-2">Create your order</h2>
        <p class="text-sm text-slate-500 mb-4">As a sales user, you can place new orders here. Order history is available to managers only.</p>
      </div>

      <!-- Create Order Modal -->
      <CreateOrderModal
        v-if="showCreateModal"
        @close="showCreateModal = false"
        @save="handleOrderSave"
      />

      <!-- Order Detail Modal -->
      <OrderDetailModal
        v-if="showDetailModal"
        :order="selectedOrder"
        @close="showDetailModal = false"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'
import CreateOrderModal from '../components/CreateOrderModal.vue'
import OrderDetailModal from '../components/OrderDetailModal.vue'
import { usePermissions } from '../composables/usePermissions'

const { canCreateOrders, isManager, isAdmin } = usePermissions()
const canViewOrders = computed(() => isManager() || isAdmin())
const canCancelOrders = isManager() // Only managers can cancel orders

const orders = ref([])
const loading = ref(true)
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const selectedOrder = ref(null)

const loadOrders = async () => {
  try {
    loading.value = true
    const response = await apiClient.get('/orders')
    orders.value = response.data.data
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    loading.value = false
  }
}

const viewOrder = (order) => {
  selectedOrder.value = order
  showDetailModal.value = true
}

const handleOrderSave = async () => {
  if (canViewOrders.value) {
    await loadOrders()
  }
  showCreateModal.value = false
}

const cancelOrder = async (id) => {
  if (confirm('Are you sure you want to cancel this order?')) {
    try {
      await apiClient.post(`/orders/${id}/cancel`, { reason: 'Cancelled by user' })
      await loadOrders()
    } catch (error) {
      console.error('Failed to cancel order:', error)
    }
  }
}

onMounted(() => {
  if (canViewOrders.value) {
    loadOrders()
  }
})
</script>

<style scoped></style>
