<template>
  <div class="p-8 space-y-6 bg-slate-50 min-h-screen">
    <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <p class="text-xs uppercase tracking-[0.35em] text-blue-600 font-semibold">Branch management</p>
          <h1 class="text-4xl font-semibold text-slate-900">Branch locations</h1>
          <p class="mt-2 text-slate-600">Manage active branches, contact details, and the locations powering your inventory network.</p>
        </div>
        <button @click="openAddForm" class="rounded-3xl bg-blue-600 px-6 py-3 text-white font-semibold hover:bg-blue-700 transition shadow-lg">
          + Add Branch
        </button>
      </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-sm text-slate-500">Total branches</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ branches.length }}</p>
      </div>
      <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-sm text-slate-500">Active branches</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ activeBranches }}</p>
      </div>
      <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
        <p class="text-sm text-slate-500">Inactive branches</p>
        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ branches.length - activeBranches }}</p>
      </div>
    </div>

    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h2 class="text-xl font-semibold text-slate-900">Branch directory</h2>
          <p class="text-sm text-slate-500">View and manage all branches in the system.</p>
        </div>
      </div>

      <div v-if="loading" class="rounded-3xl border border-dashed border-slate-200 p-8 text-center text-slate-500">Loading branches...</div>
      <div v-else-if="branches.length">
        <div class="space-y-3">
          <div v-for="branch in branches" :key="branch.id" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-4 hover:shadow-md transition">
            <div class="flex-1">
              <h3 class="font-semibold text-slate-900">{{ branch.name }}</h3>
              <div class="mt-2 flex flex-wrap gap-3 text-sm text-slate-600">
                <span class="inline-flex items-center gap-1">
                  <span class="text-slate-400">Code:</span>
                  <span class="font-medium">{{ branch.code }}</span>
                </span>
                <span v-if="branch.address" class="inline-flex items-center gap-1">
                  <span class="text-slate-400">📍</span>
                  <span>{{ branch.address }}</span>
                </span>
                <span v-if="branch.phone" class="inline-flex items-center gap-1">
                  <span class="text-slate-400">📞</span>
                  <span>{{ branch.phone }}</span>
                </span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span :class="branch.status === 'active' ? 'inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600'">
                {{ branch.status ?? 'active' }}
              </span>
              <button @click="editBranch(branch)" class="px-3 py-2 rounded-lg text-blue-600 hover:bg-blue-50 font-medium transition">Edit</button>
              <button @click="deleteBranch(branch.id)" class="px-3 py-2 rounded-lg text-red-600 hover:bg-red-50 font-medium transition">Delete</button>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="rounded-3xl border border-dashed border-slate-200 p-8 text-center">
        <p class="text-slate-500 text-lg mb-4">No branches yet.</p>
        <button @click="openAddForm" class="rounded-3xl bg-blue-600 px-6 py-2 text-white font-semibold hover:bg-blue-700 transition">
          Create your first branch
        </button>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-4">
        <h2 class="text-2xl font-semibold text-slate-900">{{ isEditing ? 'Edit Branch' : 'Add New Branch' }}</h2>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Branch Name *</label>
          <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Downtown Store" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Branch Code *</label>
          <input v-model="form.code" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., DT-001" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
          <input v-model="form.address" type="text" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Street address" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
          <input v-model="form.phone" type="tel" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Phone number" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input v-model="form.email" type="email" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Email address" />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
          <select v-model="form.status" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="flex gap-3 pt-4">
          <button @click="closeModal" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-slate-700 font-medium hover:bg-slate-50 transition">Cancel</button>
          <button @click="saveBranch" :disabled="saving" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 transition disabled:opacity-50">
            {{ saving ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import apiClient from '../services/api'

const branches = ref([])
const loading = ref(true)
const saving = ref(false)
const showModal = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

const form = ref({
  name: '',
  code: '',
  address: '',
  phone: '',
  email: '',
  status: 'active'
})

const activeBranches = computed(() => branches.value.filter(branch => branch.status === 'active').length)

const resetForm = () => {
  form.value = {
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    status: 'active'
  }
  isEditing.value = false
  editingId.value = null
}

const openAddForm = () => {
  resetForm()
  showModal.value = true
}

const editBranch = (branch) => {
  form.value = { ...branch }
  isEditing.value = true
  editingId.value = branch.id
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const saveBranch = async () => {
  if (!form.value.name || !form.value.code) {
    alert('Please fill in name and code')
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await apiClient.put(`/branches/${editingId.value}`, form.value)
    } else {
      await apiClient.post('/branches', form.value)
    }
    closeModal()
    await fetchBranches()
  } catch (error) {
    console.error('Error saving branch:', error)
    alert('Error saving branch: ' + (error.response?.data?.message || error.message))
  } finally {
    saving.value = false
  }
}

const deleteBranch = async (id) => {
  if (!confirm('Are you sure you want to delete this branch?')) {
    return
  }

  try {
    await apiClient.delete(`/branches/${id}`)
    await fetchBranches()
  } catch (error) {
    console.error('Error deleting branch:', error)
    alert('Error deleting branch: ' + (error.response?.data?.message || error.message))
  }
}

const fetchBranches = async () => {
  loading.value = true
  try {
    const response = await apiClient.get('/branches')
    branches.value = response.data.data || response.data
  } catch (error) {
    console.error('Failed to load branches:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchBranches()
})
</script>

<style scoped></style>
