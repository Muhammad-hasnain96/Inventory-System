<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Modern Top Navbar -->
    <nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50 ml-64 w-[calc(100%-16rem)]">
      <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-end h-16 gap-4">
          <!-- Right side - Notifications & User -->
          <div class="flex items-center gap-4">
            <!-- Notifications -->
            <button class="p-2 text-slate-400 hover:text-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg">
              <span class="sr-only">View notifications</span>
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.618 4.618A9.955 9.955 0 0112 2c5.523 0 10 4.477 10 10 0 2.1-.642 4.07-1.757 5.757L4.618 4.618z" />
              </svg>
            </button>

            <!-- User menu -->
            <div v-if="user" class="relative">
              <button
                @click="showUserMenu = !showUserMenu"
                class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                  <span class="text-white font-semibold text-sm">{{ user.name.charAt(0).toUpperCase() }}</span>
                </div>
                <div class="hidden md:block text-left">
                  <p class="text-sm font-medium text-slate-900">{{ user.name }}</p>
                  <p class="text-xs text-slate-500">{{ getRoleDisplay(user.role.name) }}</p>
                </div>
                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <!-- User dropdown menu -->
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50"
              >
                <div class="px-4 py-2 border-b border-slate-200">
                  <p class="text-sm font-medium text-slate-900">{{ user.name }}</p>
                  <p class="text-xs text-slate-500">{{ user.email }}</p>
                </div>
                <button
                  @click="logout"
                  class="w-full text-left px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 flex items-center gap-2"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  Sign out
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Layout -->
    <div v-if="user" class="flex min-h-screen">
      <!-- Modern Dark Sidebar -->
      <aside class="w-64 bg-slate-900 shadow-xl fixed left-0 top-0 h-screen overflow-y-auto">
        <div class="pt-16 p-6">
          <!-- Profile Section -->
          <div class="flex items-center gap-3 mb-8 p-3 bg-slate-800 rounded-lg">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
              <span class="text-white font-semibold">{{ user.name.charAt(0).toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
              <p class="text-xs text-slate-400">{{ getRoleDisplay(user.role.name) }}</p>
              <p v-if="user?.branch" class="text-xs text-slate-500">{{ user.branch.name }}</p>
            </div>
          </div>

          <!-- Navigation Menu -->
          <nav class="space-y-2">
            <!-- Dashboard -->
            <router-link
              to="/app/dashboard"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span class="font-medium">Dashboard</span>
            </router-link>

            <!-- Products -->
            <router-link
              v-if="canManageProducts"
              to="/app/products"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              <span class="font-medium">Products</span>
            </router-link>

            <!-- Branches -->
            <router-link
              v-if="canManageBranches"
              to="/app/branches"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9m0 9c-1.657 0-3-4.03-3-9s1.343-9 3-9m0 18c1.657 0 3-4.03 3-9s-1.343-9-3-9" />
              </svg>
              <span class="font-medium">Branches</span>
            </router-link>

            <!-- Inventory -->
            <router-link
              v-if="canManageInventory"
              to="/app/inventory"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <span class="font-medium">Inventory</span>
            </router-link>

            <!-- Orders -->
            <router-link
              v-if="canCreateOrders"
              to="/app/orders"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
              <span class="font-medium">Orders</span>
            </router-link>

            <!-- Recent Orders (Admin Only) -->
            <router-link
              v-if="isAdmin"
              to="/app/recent-orders"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-medium">Recent Orders</span>
            </router-link>

            <!-- Reports -->
            <router-link
              v-if="isAdmin"
              to="/app/reports"
              active-class="bg-blue-600 text-white shadow-lg"
              class="group flex items-center px-4 py-3 rounded-lg hover:bg-slate-800 transition-all duration-200 text-slate-300 hover:text-white"
            >
              <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="font-medium">Reports</span>
            </router-link>
          </nav>

          <!-- Quick Stats -->
          <div class="mt-8 p-4 bg-slate-800 rounded-lg">
            <h3 class="text-sm font-semibold text-white mb-3">Quick Stats</h3>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between items-center">
                <span class="text-slate-400">Products</span>
                <span class="text-white font-medium">{{ dashboardStats?.total_products || 0 }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400">Orders Today</span>
                <span class="text-white font-medium">{{ dashboardStats?.today_orders || 0 }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400">Low Stock</span>
                <span class="text-amber-400 font-medium">{{ dashboardStats?.low_stock_count || 0 }}</span>
              </div>
            </div>
          </div>

          <!-- Recent Orders (Admin Only) - Hide on Recent Orders page -->
          <div v-if="isAdmin && recentOrders.length && !isOnRecentOrdersPage" class="mt-6 p-4 bg-slate-800 rounded-lg">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-white">🛒 Recent Orders</h3>
              <router-link
                to="/app/orders"
                class="text-xs text-blue-300 hover:text-blue-200"
              >
                View All
              </router-link>
            </div>
            <div class="space-y-2 max-h-64 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-800">
              <div
                v-for="order in recentOrders.slice(0, 5)"
                :key="order.id"
                class="p-2 bg-slate-700 rounded hover:bg-slate-600 transition cursor-pointer text-xs"
                @click="goToOrders"
              >
                <div class="flex justify-between items-start gap-2">
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-blue-300 truncate">{{ order.order_number }}</p>
                    <p class="text-slate-400 text-xs truncate">{{ order.created_by?.name }}</p>
                  </div>
                  <span
                    :class="{
                      'bg-blue-600 text-blue-100': order.status === 'pending',
                      'bg-green-600 text-green-100': order.status === 'confirmed',
                      'bg-gray-600 text-gray-100': order.status === 'completed',
                      'bg-red-600 text-red-100': order.status === 'cancelled'
                    }"
                    class="px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap"
                  >
                    {{ order.status.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <p class="text-slate-300 mt-1 font-semibold">${{ parseFloat(order.total_amount).toFixed(2) }}</p>
              </div>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 ml-64 overflow-auto">
        <router-view />
      </main>
    </div>

    <!-- Auth Pages -->
    <div v-else class="min-h-screen flex items-center justify-center">
      <router-view />
    </div>

    <!-- Click outside to close user menu -->
    <div
      v-if="showUserMenu"
      @click="showUserMenu = false"
      class="fixed inset-0 z-40"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import apiClient from './services/api'
import { usePermissions } from './composables/usePermissions'

const router = useRouter()
const route = useRoute()
const user = ref(null)
const permissions = ref([])
const showUserMenu = ref(false)
const dashboardStats = ref(null)
const recentOrders = ref([])
const perms = usePermissions()

// Update permissions whenever they change
const updatePermissions = () => {
  permissions.value = perms.getPermissions()
}

const canManageBranches = computed(() => perms.canManageBranches())
const canManageProducts = computed(() => perms.canManageProducts())
const canManageInventory = computed(() => perms.canManageInventory())
const canCreateOrders = computed(() => perms.canCreateOrders())
const canViewReports = computed(() => perms.canViewReports())
const isAdmin = computed(() => perms.isAdmin())
const isOnRecentOrdersPage = computed(() => route.name === 'RecentOrders')

const getRoleDisplay = (roleName) => {
  const roleMap = {
    'super_admin': 'Admin',
    'branch_manager': 'Manager',
    'sales_user': 'Sales'
  }
  return roleMap[roleName] || roleName
}

// Fetch dashboard stats for sidebar
const fetchDashboardStats = async () => {
  try {
    const response = await apiClient.get('/dashboard')
    dashboardStats.value = response.data.data
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error)
  }
}

// Fetch recent orders for sidebar (Admin only)
const fetchRecentOrders = async () => {
  try {
    if (isAdmin.value) {
      const response = await apiClient.get('/orders')
      recentOrders.value = response.data.data || []
    }
  } catch (error) {
    console.error('Failed to fetch recent orders:', error)
  }
}

const goToOrders = () => {
  router.push('/app/orders')
}

onMounted(async () => {
  if (!route.path.startsWith('/app')) {
    return
  }

  try {
    // Get user from localStorage first
    const userStr = localStorage.getItem('user')
    if (userStr) {
      user.value = JSON.parse(userStr)
    }

    // Fetch fresh profile data
    const response = await apiClient.get('/profile')
    user.value = response.data.data.user

    // Update localStorage
    localStorage.setItem('user', JSON.stringify(response.data.data.user))
    localStorage.setItem('permissions', JSON.stringify(response.data.data.permissions))

    // Update permissions
    updatePermissions()

    // Fetch dashboard stats for sidebar
    await fetchDashboardStats()

    // Fetch recent orders for sidebar (Admin only)
    await fetchRecentOrders()

    // Refresh orders every 5 seconds
    setInterval(() => {
      fetchRecentOrders()
    }, 5000)
  } catch (error) {
    console.error('Failed to fetch profile:', error)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('permissions')
    router.push('/login')
  }
})

const logout = async () => {
  try {
    await apiClient.post('/logout')
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('permissions')
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('permissions')
    router.push('/login')
  }
}
</script>

<style scoped></style>
