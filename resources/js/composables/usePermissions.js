// Permissions service for checking user permissions in frontend
export const usePermissions = () => {
  const getPermissions = () => {
    const permsStr = localStorage.getItem('permissions')
    return permsStr ? JSON.parse(permsStr) : []
  }

  const getUser = () => {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  }

  const hasPermission = (permission) => {
    const permissions = getPermissions()
    return permissions.includes(permission)
  }

  const canManageBranches = () => {
    return hasPermission('manage_branches')
  }

  const canManageProducts = () => {
    return hasPermission('manage_products')
  }

  const canManageInventory = () => {
    return hasPermission('manage_inventory')
  }

  const canCreateOrders = () => {
    return hasPermission('create_orders')
  }

  const canViewReports = () => {
    return hasPermission('view_reports')
  }

  const getUserRole = () => {
    const user = getUser()
    return user?.role?.name || ''
  }

  const isAdmin = () => {
    return getUserRole() === 'super_admin'
  }

  const isManager = () => {
    return getUserRole() === 'branch_manager'
  }

  const isSalesUser = () => {
    return getUserRole() === 'sales_user'
  }

  return {
    getPermissions,
    getUser,
    hasPermission,
    canManageBranches,
    canManageProducts,
    canManageInventory,
    canCreateOrders,
    canViewReports,
    getUserRole,
    isAdmin,
    isManager,
    isSalesUser,
  }
}
