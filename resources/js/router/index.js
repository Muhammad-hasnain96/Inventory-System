import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import App from '../App.vue'
import Dashboard from '../pages/Dashboard.vue'
import Products from '../pages/Products.vue'
import Inventory from '../pages/Inventory.vue'
import Orders from '../pages/Orders.vue'
import RecentOrders from '../pages/RecentOrders.vue'
import Branches from '../pages/Branches.vue'
import Reports from '../pages/Reports.vue'

const routes = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/app',
    component: App,
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: Dashboard
      },
      {
        path: 'products',
        name: 'Products',
        component: Products,
        meta: { requiresPermission: 'manage_products' }
      },
      {
        path: 'branches',
        name: 'Branches',
        component: Branches,
        meta: { requiresPermission: 'manage_branches' }
      },
      {
        path: 'inventory',
        name: 'Inventory',
        component: Inventory,
        meta: { requiresPermission: 'manage_inventory' }
      },
      {
        path: 'orders',
        name: 'Orders',
        component: Orders,
        meta: { requiresPermission: 'create_orders' }
      },
      {
        path: 'recent-orders',
        name: 'RecentOrders',
        component: RecentOrders,
        meta: { adminOnly: true }
      },
      {
        path: 'reports',
        name: 'Reports',
        component: Reports,
        meta: { requiresPermission: 'view_reports' }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Check auth on route change
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const permissions = localStorage.getItem('permissions')
  const isLoginPage = to.path === '/login'

  if (!token && !isLoginPage) {
    next('/login')
  } else if (token && isLoginPage) {
    next('/app/dashboard')
  } else if (token && to.meta.adminOnly) {
    try {
      const user = localStorage.getItem('user')
      const currentUser = user ? JSON.parse(user) : null
      if (currentUser?.role?.name === 'super_admin') {
        next()
      } else {
        next('/app/dashboard')
      }
    } catch (e) {
      next('/login')
    }
  } else if (token && to.meta.requiresPermission) {
    // Check if user has the required permission
    try {
      const perms = JSON.parse(permissions || '[]')
      if (perms.includes(to.meta.requiresPermission)) {
        next()
      } else {
        // User doesn't have permission, redirect to dashboard
        next('/app/dashboard')
      }
    } catch (e) {
      next('/login')
    }
  } else {
    next()
  }
})

export default router
