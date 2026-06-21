<template>
  <div>
    <!-- ✅ If not DG, show access denied -->
    <div v-if="!isDG" class="alert alert-danger text-center py-5">
      <i class="fas fa-lock fa-3x mb-3"></i>
      <h4>Access Denied</h4>
      <p>You do not have permission to view this page.</p>
      <router-link to="/" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </router-link>
    </div>

    <!-- User Management for DG only -->
    <div v-else>
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">User Management</h5>
          <button class="btn btn-primary btn-sm" @click="openCreateModal">
            <i class="fas fa-plus"></i> Add User
          </button>
        </div>
        <div class="card-body p-0">
          <div v-if="userStore.loading" class="text-center py-5">
            <span class="spinner-border spinner-border-sm"></span> Loading...
          </div>
          <table v-else class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in userStore.users" :key="user.id">
                <td>{{ user.id }}</td>
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="badge" :class="getRoleClass(user.role)">
                    {{ user.role?.replace('_', ' ') }}
                  </span>
                </td>
                <td>
                  <span v-if="user.role === 'dg' || user.role === 'file_dept'">
                    <span class="text-muted">—</span>
                  </span>
                  <span v-else>
                    {{ user.department?.name || 'N/A' }}
                  </span>
                </td>
                <td>
                  <button class="btn btn-sm btn-primary me-1" @click="openEditModal(user)">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" @click="confirmDelete(user)">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="userStore.users.length === 0">
                <td colspan="6" class="text-center text-muted">No users found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- User Form Modal -->
      <div class="modal show d-block" tabindex="-1" v-if="showModal" style="background: rgba(0,0,0,0.5); display: block;">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ isEdit ? 'Edit User' : 'Create User' }}</h5>
              <button type="button" class="close" @click="closeModal">×</button>
            </div>
            <div class="modal-body">
              <div v-if="error" class="alert alert-danger">{{ error }}</div>

              <form @submit.prevent="submitForm">
                <div class="mb-3">
                  <label class="form-label">Name <span class="text-danger">*</span></label>
                  <input v-model="form.name" type="text" class="form-control" required />
                </div>

                <div class="mb-3">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input v-model="form.email" type="email" class="form-control" required />
                </div>

                <div class="mb-3" v-if="!isEdit">
                  <label class="form-label">Password <span class="text-danger">*</span></label>
                  <input v-model="form.password" type="password" class="form-control" required minlength="6" />
                </div>

                <div class="mb-3">
                  <label class="form-label">Role <span class="text-danger">*</span></label>
                  <select v-model="form.role" class="form-select" @change="onRoleChange" required>
                    <option value="">Select Role</option>
                    <option value="dg">Director General</option>
                    <option value="file_dept">File Department</option>
                    <option value="vdg">Vice Director General</option>
                    <option value="department">Department</option>
                    <option value="staff">Staff</option>
                  </select>
                </div>

                <!-- Department Field - Only for VDG, Department, Staff -->
                <div class="mb-3" v-if="showDepartmentField">
                  <label class="form-label">Department</label>
                  <select v-model="form.department_id" class="form-select">
                    <option value="">Select Department</option>
                    <option v-for="dept in userStore.departments" :key="dept.id" :value="dept.id">
                      {{ dept.name }}
                    </option>
                  </select>
                  <small class="text-muted">Select a department for this user</small>
                </div>

                <div v-if="form.role === 'dg'" class="alert alert-info">
                  <i class="fas fa-info-circle"></i> DG users do not have departments assigned.
                </div>
                <div v-if="form.role === 'file_dept'" class="alert alert-info">
                  <i class="fas fa-info-circle"></i> File Department users do not have departments assigned.
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
              <button type="button" class="btn btn-primary" @click="submitForm" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                {{ loading ? 'Saving...' : (isEdit ? 'Update' : 'Create') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user'

const authStore = useAuthStore()
const userStore = useUserStore()

// ✅ Check if user is DG
const isDG = computed(() => authStore.isDG)

const showModal = ref(false)
const isEdit = ref(false)
const loading = ref(false)
const error = ref('')
const editingId = ref(null)

const form = reactive({
  name: '',
  email: '',
  password: '',
  role: '',
  department_id: '',
})

const showDepartmentField = computed(() => {
  return form.role && !['dg', 'file_dept'].includes(form.role)
})

const getRoleClass = (role) => {
  const classes = {
    'file_dept': 'badge-primary',
    'dg': 'badge-danger',
    'vdg': 'badge-warning',
    'department': 'badge-success',
    'staff': 'badge-info'
  }
  return classes[role] || 'badge-secondary'
}

const onRoleChange = () => {
  if (['dg', 'file_dept'].includes(form.role)) {
    form.department_id = ''
  }
}

const openCreateModal = () => {
  isEdit.value = false
  editingId.value = null
  resetForm()
  error.value = ''
  showModal.value = true
}

const openEditModal = (user) => {
  isEdit.value = true
  editingId.value = user.id
  form.name = user.name
  form.email = user.email
  form.role = user.role
  form.department_id = user.department_id || ''
  error.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.name = ''
  form.email = ''
  form.password = ''
  form.role = ''
  form.department_id = ''
}

const submitForm = async () => {
  error.value = ''
  loading.value = true

  try {
    const userData = {
      name: form.name,
      email: form.email,
      role: form.role,
    }

    if (!isEdit.value) {
      userData.password = form.password
    }

    if (!['dg', 'file_dept'].includes(form.role)) {
      userData.department_id = form.department_id || null
    }

    let result
    if (isEdit.value) {
      result = await userStore.updateUser(editingId.value, userData)
    } else {
      result = await userStore.createUser(userData)
    }

    if (result.success) {
      closeModal()
    } else {
      error.value = result.message || 'Operation failed'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}

const confirmDelete = async (user) => {
  if (!confirm(`Are you sure you want to delete "${user.name}"?`)) return

  const result = await userStore.deleteUser(user.id)
  if (result.success) {
    alert(result.message)
  } else {
    alert(result.message || 'Delete failed')
  }
}

onMounted(async () => {
  // Only fetch if DG
  if (isDG.value) {
    await userStore.fetchUsers()
    await userStore.fetchDepartments()
  }
})
</script>
