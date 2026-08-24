<template>
  <div class="p-8">
    <!-- Access Denied Message -->
    <div v-if="!canManageInventory" class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
      <h2 class="text-2xl font-bold text-red-700 mb-2">Access Denied</h2>
      <p class="text-red-600">You don't have permission to manage inventory. This feature is only available to Administrators and Branch Managers.</p>
    </div>

    <template v-else>
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-800">Inventory Management</h1>
          <p class="text-sm text-gray-500 mt-1">View branch inventory and stock status in one place.</p>
        </div>
        <div v-if="isAdmin && branches.length" class="w-full max-w-xs">
          <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
          <select
            v-model="selectedBranchId"
            @change="loadInventory"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white"
          >
            <option value="">All Branches</option>
            <option v-for="branch in branches" :key="branch.id" :value="branch.id">
              {{ branch.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search products by name or SKU..."
            class="w-full max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
          />
          <select
            v-model="statusFilter"
            @change="loadInventory"
            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white"
          >
            <option value="">All Status</option>
            <option value="ok">✅ In Stock</option>
            <option value="low">⚠️ Low Stock</option>
          </select>
        </div>
        <div class="text-sm text-gray-600">
          Showing {{ filteredInventory.length }} of {{ inventory.length }} items
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6 flex gap-2 border-b">
        <button
          @click="activeTab = 'all'"
          :class="activeTab === 'all' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
          class="px-4 py-2 font-medium"
        >
          📦 All Items
        </button>
        <button
          @click="activeTab = 'low'"
          :class="activeTab === 'low' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600'"
          class="px-4 py-2 font-medium"
        >
          ⚠️ Low Stock
        </button>
      </div>

      <div v-if="loading" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Product</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">SKU</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Quantity</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Threshold</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Status</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Reorder</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="n in 5" :key="n" class="animate-pulse">
              <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-32"></div></td>
              <td class="px-6 py-4"><div class="h-4 bg-gray-200 rounded w-24"></div></td>
              <td class="px-6 py-4 text-right"><div class="h-4 bg-gray-200 rounded w-12 ml-auto"></div></td>
              <td class="px-6 py-4 text-right"><div class="h-4 bg-gray-200 rounded w-12 ml-auto"></div></td>
              <td class="px-6 py-4 text-center"><div class="h-6 bg-gray-200 rounded-full w-16 mx-auto"></div></td>
              <td class="px-6 py-4 text-center"><div class="h-6 bg-gray-200 rounded-full w-20 mx-auto"></div></td>
              <td class="px-6 py-4 text-center"><div class="h-8 bg-gray-200 rounded w-20 mx-auto"></div></td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- All Items Table -->
      <div v-else-if="activeTab === 'all' && inventory.length" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Product</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">SKU</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Quantity</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Threshold</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Status</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Reorder</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="item in filteredInventory" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-medium">{{ item.product?.name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ item.product?.sku }}</td>
              <td class="px-6 py-4 text-right font-bold">{{ item.quantity }}</td>
              <td class="px-6 py-4 text-right text-gray-600">{{ item.low_stock_threshold }}</td>
              <td class="px-6 py-4 text-center">
                <span
                  :class="getStockHealthClass(item)"
                  class="px-3 py-1 rounded-full text-sm font-medium"
                >
                  {{ getStockHealthStatus(item) }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <span
                  v-if="item.quantity <= item.low_stock_threshold"
                  class="bg-orange-100 text-orange-800 px-3 py-1 rounded-full text-sm font-medium"
                >
                  Order {{ Math.max(0, item.low_stock_threshold * 2 - item.quantity) }} more
                </span>
                <span v-else class="text-gray-400 text-sm">No reorder needed</span>
              </td>
              <td class="px-6 py-4 text-center">
                <button
                  @click="showStockModal(item, 'add')"
                  class="text-green-600 hover:text-green-800 mr-3 text-sm font-medium"
                >
                  ➕ Add
                </button>
                <button
                  @click="showStockModal(item, 'adjust')"
                  class="text-orange-600 hover:text-orange-800 text-sm font-medium"
                >
                  🔄 Adjust
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Low Stock Items -->
      <div v-else-if="activeTab === 'low' && lowStockItems.length" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Product</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">SKU</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Current</th>
              <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Threshold</th>
              <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr v-for="item in lowStockItems" :key="item.id" class="hover:bg-gray-50 border-l-4 border-red-500">
              <td class="px-6 py-4 font-medium">{{ item.product?.name }}</td>
              <td class="px-6 py-4 text-gray-600">{{ item.product?.sku }}</td>
              <td class="px-6 py-4 text-right font-bold text-red-600">{{ item.quantity }}</td>
              <td class="px-6 py-4 text-right text-gray-600">{{ item.low_stock_threshold }}</td>
              <td class="px-6 py-4 text-center">
                <button
                  @click="showStockModal(item, 'add')"
                  class="px-3 py-1 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm"
                >
                  Restock
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
        <p v-if="activeTab === 'low'">No low stock items. Everything is well-stocked!</p>
        <p v-else>No inventory data available.</p>
      </div>

      <!-- Stock Modal -->
      <StockModal
        v-if="showModal"
        :inventory="selectedInventory"
        :mode="modalMode"
        @close="showModal = false"
        @save="handleStockSave"
      />
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'
import StockModal from '../components/StockModal.vue'
import { usePermissions } from '../composables/usePermissions'

const perms = usePermissions()
const canManageInventory = computed(() => perms.canManageInventory())
const isAdmin = computed(() => perms.isAdmin())

const inventory = ref([])
const lowStockItems = ref([])
const branches = ref([])
const selectedBranchId = ref('')
const loading = ref(true)
const showModal = ref(false)
const selectedInventory = ref(null)
const modalMode = ref('add')
const activeTab = ref('all')
const searchQuery = ref('')
const statusFilter = ref('')

const filteredInventory = computed(() => {
  let items = inventory.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    items = items.filter(item =>
      item.product?.name?.toLowerCase().includes(query) ||
      item.product?.sku?.toLowerCase().includes(query)
    )
  }

  // Filter by status
  if (statusFilter.value) {
    if (statusFilter.value === 'ok') {
      items = items.filter(item => item.quantity > item.low_stock_threshold)
    } else if (statusFilter.value === 'low') {
      items = items.filter(item => item.quantity <= item.low_stock_threshold)
    }
  }

  return items
})

const getStockHealthStatus = (item) => {
  const ratio = item.quantity / item.low_stock_threshold
  if (ratio > 1.5) return 'Healthy'
  if (ratio > 1) return 'OK'
  if (ratio > 0.5) return 'Low'
  return 'Critical'
}

const getStockHealthClass = (item) => {
  const ratio = item.quantity / item.low_stock_threshold
  if (ratio > 1.5) return 'bg-green-100 text-green-800'
  if (ratio > 1) return 'bg-blue-100 text-blue-800'
  if (ratio > 0.5) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

const loadInventory = async () => {
  try {
    loading.value = true
    const params = {}
    if (selectedBranchId.value) {
      params.branch_id = selectedBranchId.value
    }

    const [allResponse, lowResponse] = await Promise.all([
      apiClient.get('/inventory', { params }),
      apiClient.get('/inventory/low-stock', { params })
    ])

    inventory.value = allResponse.data.data
    lowStockItems.value = lowResponse.data.data
  } catch (error) {
    console.error('Failed to load inventory:', error)
  } finally {
    loading.value = false
  }
}

const showStockModal = (item, mode) => {
  selectedInventory.value = item
  modalMode.value = mode
  showModal.value = true
}

const loadBranches = async () => {
  try {
    const response = await apiClient.get('/branches')
    branches.value = response.data.data
  } catch (error) {
    console.error('Failed to load branches:', error)
  }
}

const handleStockSave = async () => {
  await loadInventory()
  showModal.value = false
}

onMounted(async () => {
  if (isAdmin.value) {
    await loadBranches()
  }
  await loadInventory()
})
</script>

<style scoped></style>
