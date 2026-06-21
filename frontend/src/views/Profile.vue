<template>
  <div>
    <div class="card">
      <div class="card-header">
        <h5 class="card-title">User Profile</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-3 text-center">
            <img
              src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg"
              class="img-circle img-fluid"
              alt="User Image"
              style="width: 150px;"
            />
            <h5 class="mt-3">{{ user.name }}</h5>
            <p class="text-muted">{{ user.role?.replace('_', ' ') || 'User' }}</p>
            <router-link to="/" class="btn btn-sm btn-primary">
              <i class="fas fa-arrow-left"></i> Back to Dashboard
            </router-link>
          </div>
          <div class="col-md-9">
            <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>

            <table class="table table-bordered">
              <tbody>  <!-- ✅ ADD THIS -->
                <tr>
                  <th width="30%">ID</th>
                  <td>{{ user.id || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Name</th>
                  <td>{{ user.name || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Email</th>
                  <td>{{ user.email || 'N/A' }}</td>
                </tr>
                <tr>
                  <th>Role</th>
                  <td>
                    <span class="badge" :class="getRoleClass(user.role)">
                      {{ user.role?.replace('_', ' ') || 'N/A' }}
                    </span>
                  </td>
                </tr>
                <tr>
                  <th>Department</th>
                  <td>{{ user.department_id || 'Not Assigned' }}</td>
                </tr>
                <tr>
                  <th>Account Status</th>
                  <td><span class="badge badge-success">Active</span></td>
                </tr>
              </tbody>  <!-- ✅ ADD THIS -->
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const user = ref({
  id: '',
  name: '',
  email: '',
  role: '',
  department_id: null
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

onMounted(() => {
  const userData = localStorage.getItem('user')
  if (userData) {
    user.value = JSON.parse(userData)
  }
})
</script>
