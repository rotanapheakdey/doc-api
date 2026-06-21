<template>
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="/" class="brand-link">
      <span class="brand-text font-weight-light">📄 DMS</span>
    </a>

    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="https://adminlte.io/themes/v3/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ user.name || 'John Doe' }}</a>
          <small class="text-muted">{{ user.role?.replace('_', ' ') || 'Role' }}</small>
        </div>
      </div>

      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column">
          <!-- Dashboard -->
          <li class="nav-item">
            <router-link to="/" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </router-link>
          </li>

          <!-- Documents -->
          <li class="nav-item">
            <router-link :to="{ name: 'documents' }" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Documents</p>
            </router-link>
          </li>

          <!-- Upload - Only show for file_dept -->
          <li v-if="canUpload" class="nav-item">
            <router-link :to="{ name: 'upload' }" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-upload"></i>
              <p>Upload</p>
            </router-link>
          </li>

          <!-- Archive -->
          <li class="nav-item">
            <router-link :to="{ name: 'archive' }" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-archive"></i>
              <p>Archive</p>
            </router-link>
          </li>

          <!-- Users - Only show for DG -->
          <li v-if="isDG" class="nav-item">
            <router-link :to="{ name: 'users' }" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>Users</p>
            </router-link>
          </li>

          <!-- Profile -->
          <li class="nav-item">
            <router-link :to="{ name: 'profile' }" class="nav-link" active-class="active" exact-active-class="active">
              <i class="nav-icon fas fa-user"></i>
              <p>Profile</p>
            </router-link>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const user = ref({})

// ✅ Only these 2 logic lines added
const canUpload = computed(() => authStore.canUpload)
const isDG = computed(() => authStore.isDG)

onMounted(() => {
  const userData = localStorage.getItem('user')
  if (userData) {
    user.value = JSON.parse(userData)
  }
})
</script>
