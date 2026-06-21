import { createRouter, createWebHistory } from 'vue-router'
import AdminLayout from '@/layouts/AdminLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/components/auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: () => import('@/views/Dashboard.vue'),
        meta: { title: 'Dashboard' }
      },
      {
        path: 'documents',
        name: 'documents',
        component: () => import('@/views/Documents.vue'),
        meta: { title: 'Documents' }
      },
      {
        path: 'documents/:id',
        name: 'document-detail',
        component: () => import('@/views/DocumentDetail.vue'),
        meta: { title: 'Document Details' }
      },
      {
        path: 'upload',
        name: 'upload',
        component: () => import('@/views/Upload.vue'),
        meta: { title: 'Upload Document' }
      },
      {
        path: 'archive',
        name: 'archive',
        component: () => import('@/views/Archive.vue'),
        meta: { title: 'Archive' }
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('@/views/Profile.vue'),
        meta: { title: 'Profile' }
      },
      // ===================== USER MANAGEMENT ROUTE =====================
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/Users.vue'),
        meta: {
          title: 'User Management',
          requiresAuth: true,
          requiredRole: ['dg'] // ✅ Only DG can access
        }
      }
    ]
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'notfound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation Guard
router.beforeEach((to, from) => {
  const token = localStorage.getItem('auth_token')
  const user = JSON.parse(localStorage.getItem('user') || '{}')

  // Check if route requires authentication
  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  // If user is logged in and tries to go to login page
  if (to.meta.guest && token) {
    return '/'
  }

  // Check role-based access
  if (to.meta.requiredRole) {
    const userRole = user.role
    if (!to.meta.requiredRole.includes(userRole)) {
      return '/'
    }
  }

  return true
})

export default router
