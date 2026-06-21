import { defineStore } from 'pinia'
import { authAPI } from '@/api/auth'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('auth_token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null'),
    isAuthenticated: !!localStorage.getItem('auth_token'),
    loading: false,
  }),

  getters: {
    userRole: (state) => state.user?.role || null,

    // Role checkers
    isFileDept: (state) => state.user?.role === 'file_dept',
    isDG: (state) => state.user?.role === 'dg',
    isVDG: (state) => state.user?.role === 'vdg',
    isDepartment: (state) => state.user?.role === 'department',
    isStaff: (state) => state.user?.role === 'staff',

     canUpload: (state) => state.user?.role === 'file_dept',

    canAssign: (state) => state.user?.role === 'dg',
    canDispatch: (state) => state.user?.role === 'file_dept',
    canVDGSign: (state) => state.user?.role === 'vdg',
    canDGSign: (state) => state.user?.role === 'dg',
    canArchive: (state) => state.user?.role === 'file_dept',

    // User Management - Only DG
    canManageUsers: (state) => state.user?.role === 'dg'
  },

  actions: {
    async login(email, password) {
      this.loading = true
      try {
        const response = await authAPI.login(email, password)
        const { access_token, user } = response.data

        this.token = access_token
        this.user = user
        this.isAuthenticated = true

        localStorage.setItem('auth_token', access_token)
        localStorage.setItem('user', JSON.stringify(user))

        return { success: true, user }
      } catch (error) {
        const message = error.response?.data?.message || 'Login failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await authAPI.logout()
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.token = null
        this.user = null
        this.isAuthenticated = false
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
      }
    },

    async getUser() {
      try {
        const response = await authAPI.getUser()
        this.user = response.data.user
        localStorage.setItem('user', JSON.stringify(this.user))
        return this.user
      } catch (error) {
        console.error('Failed to get user:', error)
        this.logout()
        return null
      }
    }
  }
})
