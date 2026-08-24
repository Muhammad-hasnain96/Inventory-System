<template>
  <div class="p-8 space-y-8 bg-slate-50 min-h-screen">
    <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm hover:shadow-md transition-shadow">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold text-slate-900">📑 Reports</h1>
          <p class="mt-2 text-slate-600">View sales and inventory reports without changing your current workflow.</p>
        </div>
        <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 border border-blue-200">
          Updated from existing dashboard endpoints
        </div>
      </div>
      <!-- Filters and Export -->
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between p-4 bg-slate-50 rounded-lg border border-slate-200">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:gap-4">
          <label class="text-sm font-medium text-slate-700">Date Range:</label>
          <select
            v-model="dateRange"
            @change="loadReports"
            class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-sm"
          >
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
          </select>

          <div v-if="isAdmin && branches.length" class="flex items-center gap-2">
            <label class="text-sm font-medium text-slate-700">Branch:</label>
            <select
              v-model="selectedBranchId"
              @change="loadReports"
              class="px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white text-sm"
            >
              <option value="">All Branches</option>
              <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            @click="exportReport('sales')"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium"
          >
            📊 Export Sales
          </button>
          <button
            @click="exportReport('inventory')"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium"
          >
            📦 Export Inventory
          </button>
        </div>
      </div>    </section>

    <div v-if="loading" class="rounded-2xl bg-white p-12 text-center text-slate-500 shadow-sm border border-slate-200">
      Loading reports...
    </div>

    <div v-else>
      <div v-if="error" class="rounded-2xl bg-rose-50 border border-rose-200 p-6 text-rose-700">
        {{ error }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="rounded-2xl bg-white shadow-sm border border-slate-200 p-6">
          <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Today&apos;s Sales</p>
          <p class="mt-4 text-3xl font-bold text-slate-900">{{ todayOrders }}</p>
          <p class="mt-2 text-sm text-slate-500">orders processed today</p>
          <p class="mt-4 text-lg font-semibold text-emerald-600">${{ todayRevenue.toFixed(2) }}</p>
          <p class="text-sm text-slate-500">revenue today</p>
        </div>

        <div class="rounded-2xl bg-white shadow-sm border border-slate-200 p-6">
          <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Inventory Summary</p>
          <p class="mt-4 text-3xl font-bold text-slate-900">{{ inventoryReport?.unique_products ?? 0 }}</p>
          <p class="mt-2 text-sm text-slate-500">unique products in stock</p>
          <p class="mt-4 text-lg font-semibold text-blue-600">{{ inventoryReport?.total_quantity_in_stock ?? 0 }}</p>
          <p class="text-sm text-slate-500">total available units</p>
        </div>

        <div class="rounded-2xl bg-white shadow-sm border border-slate-200 p-6">
          <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">Low Stock Count</p>
          <p class="mt-4 text-3xl font-bold text-slate-900">{{ inventoryReport?.low_stock_count ?? 0 }}</p>
          <p class="mt-2 text-sm text-slate-500">items below threshold</p>
          <div class="mt-4 inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 border border-amber-200">
            Based on your existing branch inventory
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900">Sales Trend</h2>
              <p class="text-sm text-slate-500 mt-1">Monthly totals from the report endpoint</p>
            </div>
          </div>
          <div class="min-h-[320px]">
            <Line :data="salesChartData" :options="salesChartOptions" />
          </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900">Low Stock Items</h2>
              <p class="text-sm text-slate-500 mt-1">Based on your current branch inventory</p>
            </div>
          </div>

          <div v-if="inventoryReport?.low_stock_items?.length" class="space-y-3">
            <div v-for="item in inventoryReport.low_stock_items.slice(0, 8)" :key="item.id" class="rounded-2xl border border-slate-200 p-4 hover:border-blue-300 transition">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="font-semibold text-slate-900">{{ item.product?.name ?? item.product_name ?? 'Unknown Product' }}</p>
                  <p class="text-sm text-slate-500 mt-1">SKU: {{ item.product?.sku ?? item.product_sku ?? 'N/A' }}</p>
                </div>
                <span class="text-sm font-semibold text-rose-600">{{ item.quantity }}</span>
              </div>
            </div>
          </div>

          <div v-else class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-slate-500">
            No low stock items found.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import apiClient from '../services/api'
import { usePermissions } from '../composables/usePermissions'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const perms = usePermissions()
const isAdmin = computed(() => perms.isAdmin())

const loading = ref(true)
const error = ref('')
const salesReport = ref({ today: [], month: [] })
const inventoryReport = ref(null)
const branches = ref([])
const selectedBranchId = ref('')
const dateRange = ref('month')

const loadReports = async () => {
  try {
    loading.value = true
    error.value = ''

    const params = {}
    if (selectedBranchId.value) {
      params.branch_id = selectedBranchId.value
    }
    params.date_range = dateRange.value

    const [salesResponse, inventoryResponse] = await Promise.all([
      apiClient.get('/dashboard/sales-report', { params }),
      apiClient.get('/dashboard/inventory-report', { params })
    ])

    salesReport.value = salesResponse.data.data
    inventoryReport.value = inventoryResponse.data.data
  } catch (err) {
    console.error('Failed to load reports:', err)
    error.value = err.response?.data?.message || 'Could not load reports at this time.'
  } finally {
    loading.value = false
  }
}

const loadBranches = async () => {
  try {
    const response = await apiClient.get('/branches')
    branches.value = response.data.data
  } catch (error) {
    console.error('Failed to load branches:', error)
  }
}

const exportReport = async (type) => {
  try {
    const params = new URLSearchParams()
    if (selectedBranchId.value) {
      params.append('branch_id', selectedBranchId.value)
    }
    params.append('type', type)
    params.append('date_range', dateRange.value)

    const response = await apiClient.get(`/reports/export?${params.toString()}`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `${type}_report_${dateRange.value}_${new Date().toISOString().split('T')[0]}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export report:', error)
    alert('Failed to export report. Please try again.')
  }
}

const normalizeReportArray = (value) => {
  if (!value) return []
  if (Array.isArray(value)) return value
  return Object.entries(value).map(([key, item]) => ({
    ...item,
    label: item.label ?? key,
    date: item.date ?? key,
  }))
}

const todayOrders = computed(() => {
  const items = normalizeReportArray(salesReport.value.today)
  return items.reduce((sum, item) => sum + (item.count || 0), 0)
})

const todayRevenue = computed(() => {
  const items = normalizeReportArray(salesReport.value.today)
  return items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0)
})

const salesChartData = computed(() => {
  const items = normalizeReportArray(salesReport.value.month)
  return {
    labels: items.map((item, index) => item.label || item.date || `Day ${index + 1}`),
    datasets: [
      {
        label: 'Revenue',
        data: items.map(item => parseFloat(item.total) || 0),
        borderColor: 'rgb(37, 99, 235)',
        backgroundColor: 'rgba(37, 99, 235, 0.15)',
        tension: 0.35,
        fill: true,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: 'rgb(37, 99, 235)',
        borderWidth: 3,
      }
    ]
  }
})

const salesChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      callbacks: {
        label: (context) => `$${context.parsed.y?.toLocaleString() || 0}`,
      }
    }
  },
  scales: {
    x: {
      grid: { display: false }
    },
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(15, 23, 42, 0.08)' },
      ticks: {
        callback: (value) => `$${value}`
      }
    }
  }
}

onMounted(async () => {
  if (isAdmin.value) {
    await loadBranches()
  }
  await loadReports()
})
</script>

<style scoped></style>
