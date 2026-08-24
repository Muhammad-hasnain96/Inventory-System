<template>
  <div class="min-h-screen bg-slate-50">
    <!-- Hero Section with Welcome -->
    <section class="bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
          <div class="space-y-4">
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-sm px-4 py-2 text-sm font-semibold">
              <span class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></span>
              Live Dashboard
            </div>
            <h1 class="text-4xl lg:text-5xl font-bold">Welcome back, {{ user?.name?.split(' ')[0] || 'User' }}!</h1>
            <p class="text-xl text-blue-100 leading-relaxed max-w-2xl">
              Here's what's happening with your inventory today. Monitor performance, track trends, and manage your operations efficiently.
            </p>
            <div class="flex flex-wrap gap-3">
              <div class="inline-flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-4 py-2 text-sm">
                <span class="text-green-300">📈</span>
                {{ dashboard?.sales_growth ?? 0 }}% growth this month
              </div>
              <div class="inline-flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-4 py-2 text-sm">
                <span class="text-blue-300">🔄</span>
                Last updated: {{ refreshedAt }}
              </div>
            </div>
          </div>

          <!-- Current Branch Info -->
          <div class="lg:shrink-0 space-y-4">
            <!-- Branch Selector for Admin -->
            <div v-if="isAdmin && branches.length" class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                  <span class="text-sm">🔄</span>
                </div>
                <div class="flex-1">
                  <label for="branch-select" class="block text-xs font-medium text-blue-100 mb-1">View Branch Data</label>
                  <select
                    id="branch-select"
                    v-model="selectedBranchId"
                    class="w-full bg-white border-2 border-blue-300 rounded-lg px-3 py-2 text-sm text-slate-900 font-medium placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 transition"
                  >
                    <option value="all" class="text-slate-900 font-medium">All Branches</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id" class="text-slate-900 font-medium">
                      {{ branch.name }} ({{ branch.code }})
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Current Branch Display -->
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                  <span class="text-2xl">🏢</span>
                </div>
                <div>
                  <p class="text-sm font-medium text-blue-100">Current Branch</p>
                  <p class="text-xl font-bold">{{ dashboard?.branch?.name ?? 'All Branches' }}</p>
                  <p class="text-sm text-blue-200">{{ dashboard?.branch?.code ?? 'Combined View' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="animate-pulse space-y-8">
        <!-- Stats Cards Loading -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div v-for="n in 4" :key="n" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <div class="h-4 bg-slate-200 rounded w-24 mb-4"></div>
            <div class="h-8 bg-slate-200 rounded w-16 mb-2"></div>
            <div class="h-3 bg-slate-200 rounded w-32"></div>
          </div>
        </div>
        <!-- Charts Loading -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 h-80"></div>
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 h-80"></div>
          <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 h-80"></div>
        </div>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Key Metrics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Products -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 group">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl">
              <span class="text-2xl text-white">📦</span>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900">{{ dashboard?.total_products ?? 0 }}</p>
              <p class="text-xs text-slate-500 uppercase tracking-wide">Total Products</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">{{ dashboard?.active_products ?? 0 }} active</span>
            <div class="flex items-center text-green-600 text-sm font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
              +12%
            </div>
          </div>
        </div>

        <!-- Total Inventory -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 group">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl">
              <span class="text-2xl text-white">📊</span>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900">{{ dashboard?.total_stock ?? 0 }}</p>
              <p class="text-xs text-slate-500 uppercase tracking-wide">Total Stock</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">{{ dashboard?.low_stock_count ?? 0 }} low stock</span>
            <div class="flex items-center text-amber-600 text-sm font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              Alert
            </div>
          </div>
        </div>

        <!-- Today's Orders -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 group">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl">
              <span class="text-2xl text-white">🛒</span>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900">{{ dashboard?.today_orders ?? 0 }}</p>
              <p class="text-xs text-slate-500 uppercase tracking-wide">Today's Orders</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">Orders processed</span>
            <div class="flex items-center text-emerald-600 text-sm font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              Active
            </div>
          </div>
        </div>

        <!-- Revenue -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 hover:shadow-lg transition-all duration-300 group">
          <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-xl">
              <span class="text-2xl text-white">💰</span>
            </div>
            <div class="text-right">
              <p class="text-2xl font-bold text-slate-900">${{ (dashboard?.monthly_total ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
              <p class="text-xs text-slate-500 uppercase tracking-wide">Monthly Revenue</p>
            </div>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-slate-600">This month</span>
            <div :class="dashboard?.sales_growth >= 0 ? 'text-green-600' : 'text-red-600'" class="flex items-center text-sm font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path v-if="dashboard?.sales_growth >= 0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" />
              </svg>
              {{ dashboard?.sales_growth >= 0 ? '+' : '' }}{{ dashboard?.sales_growth ?? 0 }}%
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sales Trends Chart -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
            <div>
              <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span class="text-2xl">📈</span>
                Sales Trends
              </h2>
              <p class="text-sm text-slate-500 mt-1">Daily revenue and growth tracking</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-gradient-to-r from-emerald-50 to-green-50 px-4 py-2 text-sm font-semibold text-emerald-700 border border-emerald-200">
              <span class="mr-2">📊</span>
              +{{ dashboard?.sales_growth ?? 0 }}% growth
            </div>
          </div>

          <div v-if="dashboard.monthly_sales_chart?.length" class="min-h-[360px] rounded-3xl bg-slate-50/80 p-4">
            <Line :data="revenueChartData" :options="chartOptions" />
          </div>
          <div v-else class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center">
            <div class="text-6xl mb-4">📊</div>
            <p class="font-semibold text-slate-900 mb-2">No sales data available yet</p>
            <p class="text-sm text-slate-500">Sales data will appear here once orders are processed</p>
          </div>
        </div>

        <!-- Branch Distribution -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <span class="text-2xl">🏢</span>
              Stock Distribution
            </h2>
            <p class="text-sm text-slate-500 mt-1">Inventory across branches</p>
          </div>

          <div v-if="dashboard.branch_summary?.length" class="flex justify-center">
            <div style="max-width: 280px;">
              <Doughnut :data="branchChartData" :options="doughnutOptions" />
            </div>
          </div>
          <div v-else class="text-center py-8">
            <div class="text-6xl mb-4">📦</div>
            <p class="font-semibold text-slate-900 mb-2">No branch data available</p>
            <p class="text-sm text-slate-500">Branch data will appear here</p>
          </div>
        </div>
      </div>

      <!-- Additional Charts Row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Monthly Sales Bar Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <span class="text-2xl">📊</span>
              Monthly Performance
            </h2>
            <p class="text-sm text-slate-500 mt-1">Sales volume by month</p>
          </div>

          <div v-if="dashboard.monthly_sales_chart?.length" class="min-h-[300px]">
            <Bar :data="monthlyBarChartData" :options="barChartOptions" />
          </div>
          <div v-else class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center">
            <div class="text-6xl mb-4">📊</div>
            <p class="font-semibold text-slate-900 mb-2">No monthly data available</p>
            <p class="text-sm text-slate-500">Monthly performance data will appear here</p>
          </div>
        </div>

        <!-- Top Products Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
              <span class="text-2xl">⭐</span>
              Top Products
            </h2>
            <p class="text-sm text-slate-500 mt-1">Best performing items</p>
          </div>

          <div v-if="dashboard.top_products?.length" class="space-y-4">
            <div v-for="(item, index) in dashboard.top_products.slice(0, 5)" :key="item.id"
                 class="flex items-center gap-4 p-3 rounded-lg hover:bg-slate-50 transition-colors">
              <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                {{ index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-slate-900 truncate">{{ item.name }}</p>
                <p class="text-sm text-slate-500">{{ item.total_quantity }} units sold</p>
              </div>
              <div class="text-right">
                <p class="font-bold text-slate-900">$ {{ parseFloat(item.total_sales).toFixed(2) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <div class="text-6xl mb-4">⭐</div>
            <p class="font-semibold text-slate-900 mb-2">No product data available</p>
            <p class="text-sm text-slate-500">Top products will appear here</p>
          </div>
        </div>
      </div>

      <!-- Tables Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="flex items-center justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span class="text-2xl">🚨</span>
                Low Stock Alerts
              </h2>
              <p class="text-sm text-slate-500 mt-1">Items requiring immediate restock</p>
            </div>
            <div class="inline-flex items-center rounded-full bg-gradient-to-r from-rose-50 to-red-50 px-4 py-2 text-xs font-bold text-rose-700 border border-rose-200">
              <span class="mr-1.5">⚡</span>
              PRIORITY
            </div>
          </div>

          <div v-if="dashboard.low_stock_items?.length" class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">Product</th>
                  <th class="px-4 py-3 text-left font-semibold text-slate-700">Branch</th>
                  <th class="px-4 py-3 text-right font-semibold text-slate-700">Qty</th>
                  <th class="px-4 py-3 text-right font-semibold text-slate-700">Min</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in dashboard.low_stock_items.slice(0, 8)" :key="`${item.id}-${item.branch?.id || item.branch}`" class="hover:bg-slate-50 transition-colors">
                  <td class="px-4 py-3 font-medium text-slate-800">{{ item.product?.name }}</td>
                  <td class="px-4 py-3 text-slate-600">{{ item.branch?.name ?? item.branch }}</td>
                  <td class="px-4 py-3 text-right font-bold text-rose-600">{{ item.quantity }}</td>
                  <td class="px-4 py-3 text-right text-slate-600">{{ item.low_stock_threshold }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-12">
            <div class="text-6xl mb-4">✅</div>
            <p class="font-semibold text-slate-900 mb-2">All inventory levels are healthy</p>
            <p class="text-sm text-slate-500">No items require immediate attention</p>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-lg transition-all duration-300">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                <span class="text-2xl">📋</span>
                Recent Activity
              </h2>
              <p class="text-sm text-slate-500 mt-1">Latest system activities</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
              Live Feed
            </span>
          </div>

          <div class="space-y-4">
            <template v-if="dashboard.recent_activity?.length">
              <div v-for="item in dashboard.recent_activity" :key="item.id" class="flex items-start gap-3 p-3 rounded-lg bg-slate-50">
                <div :class="[`w-8 h-8 rounded-full flex items-center justify-center`, item.bg_class]">
                  <span class="text-sm">{{ item.icon }}</span>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
                  <p class="text-xs text-slate-500">{{ item.description }}</p>
                  <p class="text-xs text-slate-400 mt-1">{{ item.time }}</p>
                </div>
              </div>
            </template>
            <div v-else class="text-center py-12 rounded-2xl bg-slate-50">
              <p class="text-sm text-slate-500">No recent activity available yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Line, Doughnut, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Filler,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import apiClient from '../services/api'
import MetricCard from '../components/MetricCard.vue'
import { usePermissions } from '../composables/usePermissions'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, ArcElement, Filler, Title, Tooltip, Legend)

const perms = usePermissions()
const isAdmin = computed(() => perms.isAdmin())

const dashboard = ref({
  total_products: 0,
  active_products: 0,
  total_stock: 0,
  today_orders: 0,
  today_sales_value: 0,
  monthly_total: 0,
  monthly_sales_chart: [],
  sales_growth: 0,
  branch_summary: [],
  top_products: [],
  low_stock_count: 0,
  low_stock_items: [],
  recent_activity: [],
  branch: null
})
const branches = ref([])
const selectedBranchId = ref('all')
const loading = ref(true)
const user = ref(null)

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    intersect: false,
    mode: 'index',
  },
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(15, 23, 42, 0.95)',
      titleColor: '#f8fafc',
      bodyColor: '#e2e8f0',
      borderColor: 'rgba(148, 163, 184, 0.2)',
      borderWidth: 1,
      cornerRadius: 12,
      displayColors: false,
      padding: 16,
      titleFont: {
        size: 14,
        weight: '600',
        family: 'Inter, system-ui, sans-serif',
      },
      bodyFont: {
        size: 13,
        family: 'Inter, system-ui, sans-serif',
      },
      callbacks: {
        title: function(context) {
          return context[0].raw?.fullDate || context[0].label;
        },
        label: function(context) {
          return `Revenue: $${context.parsed.y?.toLocaleString() || 0}`;
        },
      },
      shadowOffsetX: 0,
      shadowOffsetY: 4,
      shadowBlur: 12,
      shadowColor: 'rgba(0, 0, 0, 0.1)',
    },
  },
  elements: {
    line: {
      tension: 0.35,
      borderWidth: 4,
      borderCapStyle: 'round',
      borderJoinStyle: 'round',
      fill: true,
    },
    point: {
      radius: 3,
      hoverRadius: 7,
      pointStyle: 'circle',
      backgroundColor: '#ffffff',
      borderColor: '#4338ca',
      borderWidth: 3,
      hoverBorderWidth: 4,
      hoverBorderColor: '#4338ca',
      hoverBackgroundColor: '#ffffff',
      shadowOffsetX: 0,
      shadowOffsetY: 2,
      shadowBlur: 10,
      shadowColor: 'rgba(67, 56, 202, 0.18)',
    },
  },
  hover: {
    mode: 'index',
    intersect: false,
  },
  scales: {
    x: {
      display: true,
      grid: {
        display: true,
        color: 'rgba(148, 163, 184, 0.08)',
        drawBorder: false,
      },
      ticks: {
        maxRotation: 0,
        minRotation: 0,
        font: {
          size: 12,
          weight: '500',
          family: 'Inter, system-ui, sans-serif',
        },
        color: '#64748b',
        padding: 10,
      },
      border: {
        display: false,
      },
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: {
        color: 'rgba(148, 163, 184, 0.14)',
        drawBorder: false,
        borderDash: [4, 6],
      },
      ticks: {
        font: {
          size: 12,
          weight: '500',
          family: 'Inter, system-ui, sans-serif',
        },
        color: '#64748b',
        padding: 12,
        callback: function(value) {
          if (value >= 1000) {
            return '$' + (value / 1000).toFixed(1) + 'k';
          }
          return '$' + value.toLocaleString();
        }
      },
      border: {
        display: false,
      },
    }
  },
  layout: {
    padding: {
      top: 20,
      right: 20,
      bottom: 20,
      left: 20,
    },
  },
  spanGaps: true,
  animation: {
    duration: 2000,
    easing: 'easeInOutQuart',
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 20,
        font: {
          size: 12
        }
      }
    }
  }
}

const revenueChartData = computed(() => ({
  labels: dashboard.value?.monthly_sales_chart?.map(item => item.label) || [],
  datasets: [
    {
      label: 'Daily Revenue',
      data: dashboard.value?.monthly_sales_chart?.map(item => ({
        x: item.label,
        y: item.value,
        fullDate: item.full_date,
      })) || [],
      borderColor: '#4338ca',
      backgroundColor: (context) => {
        const ctx = context.chart.ctx;
        const gradient = ctx.createLinearGradient(0, 0, 0, context.chart.height);
        gradient.addColorStop(0, 'rgba(67, 56, 202, 0.28)');
        gradient.addColorStop(0.4, 'rgba(67, 56, 202, 0.14)');
        gradient.addColorStop(1, 'rgba(67, 56, 202, 0.04)');
        return gradient;
      },
      tension: 0.35,
      fill: true,
      borderWidth: 4,
      pointRadius: 3,
      pointHoverRadius: 7,
      pointBackgroundColor: '#ffffff',
      pointBorderColor: '#4338ca',
      pointBorderWidth: 3,
      pointHoverBorderWidth: 4,
      pointHoverBorderColor: '#4338ca',
      pointHoverBackgroundColor: '#ffffff',
      shadowOffsetX: 0,
      shadowOffsetY: 6,
      shadowBlur: 18,
      shadowColor: 'rgba(67, 56, 202, 0.18)',
    }
  ]
}))

const branchChartData = computed(() => {
  const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899']
  return {
    labels: dashboard.value?.branch_summary?.map(item => item.name) || [],
    datasets: [
      {
        data: dashboard.value?.branch_summary?.map(item => item.stock) || [],
        backgroundColor: colors.slice(0, dashboard.value?.branch_summary?.length || 0),
        borderColor: '#fff',
        borderWidth: 2,
      }
    ]
  }
})

const monthlyBarChartData = computed(() => ({
  labels: dashboard.value?.monthly_sales_chart?.map(item => item.label) || [],
  datasets: [
    {
      label: 'Monthly Sales',
      data: dashboard.value?.monthly_sales_chart?.map(item => ({
        x: item.label,
        y: item.value,
        fullDate: item.full_date,
      })) || [],
      backgroundColor: 'rgba(59, 130, 246, 0.8)',
      borderColor: '#3b82f6',
      borderWidth: 1,
      borderRadius: 4,
      borderSkipped: false,
      hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)',
    }
  ]
}))

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false,
    },
    tooltip: {
      enabled: true,
      backgroundColor: 'rgba(15, 23, 42, 0.95)',
      titleColor: '#f8fafc',
      bodyColor: '#e2e8f0',
      borderColor: 'rgba(148, 163, 184, 0.2)',
      borderWidth: 1,
      cornerRadius: 12,
      displayColors: false,
      padding: 16,
      titleFont: {
        size: 14,
        weight: '600',
        family: 'Inter, system-ui, sans-serif',
      },
      bodyFont: {
        size: 13,
        family: 'Inter, system-ui, sans-serif',
      },
      callbacks: {
        title: function(context) {
          return context[0].raw?.fullDate || context[0].label;
        },
        label: function(context) {
          return `Revenue: $${context.parsed.y?.toLocaleString() || 0}`;
        },
      },
    },
  },
  scales: {
    x: {
      display: true,
      grid: {
        display: false,
      },
      ticks: {
        font: {
          size: 12,
          weight: '500',
          family: 'Inter, system-ui, sans-serif',
        },
        color: '#64748b',
        padding: 8,
      },
      border: {
        display: false,
      },
    },
    y: {
      display: true,
      beginAtZero: true,
      grid: {
        color: 'rgba(148, 163, 184, 0.1)',
        drawBorder: false,
      },
      ticks: {
        font: {
          size: 12,
          weight: '500',
          family: 'Inter, system-ui, sans-serif',
        },
        color: '#64748b',
        padding: 12,
        callback: function(value) {
          if (value >= 1000) {
            return '$' + (value / 1000).toFixed(1) + 'k';
          }
          return '$' + value.toLocaleString();
        }
      },
      border: {
        display: false,
      },
    }
  },
  layout: {
    padding: {
      top: 20,
      right: 20,
      bottom: 20,
      left: 20,
    },
  },
  animation: {
    duration: 1500,
    easing: 'easeInOutQuart',
  },
}

const refreshedAt = computed(() => {
  if (!dashboard.value?.updated_at) {
    return 'Recent'
  }
  return new Date(dashboard.value.updated_at).toLocaleString()
})

const loadDashboard = async () => {
  try {
    const params = {}
    if (selectedBranchId.value) {
      params.branch_id = selectedBranchId.value
    }
    const response = await apiClient.get('/dashboard', { params })
    if (response.data.success) {
      dashboard.value = response.data.data
      console.log('✓ Dashboard data updated:', dashboard.value)
    } else {
      console.error('Dashboard API returned error:', response.data.message)
    }
  } catch (error) {
    console.error('✗ Failed to load dashboard:', error.response?.data || error.message)
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

let interval = null

onMounted(async () => {
  // Get user data
  try {
    const userStr = localStorage.getItem('user')
    if (userStr) {
      user.value = JSON.parse(userStr)
    }
  } catch (error) {
    console.error('Failed to parse user data:', error)
  }

  if (isAdmin.value) {
    await loadBranches()
  }
  await loadDashboard()
  // Refresh every 10 seconds for real-time updates
  interval = setInterval(async () => {
    if (isAdmin.value) {
      await loadBranches()
    }
    await loadDashboard()
  }, 10000)
})

// Watch for branch selection changes
watch(selectedBranchId, async (newBranchId) => {
  loading.value = true
  await loadDashboard()
})

onBeforeUnmount(() => {
  if (interval) {
    clearInterval(interval)
  }
})
</script>

<style scoped></style>
