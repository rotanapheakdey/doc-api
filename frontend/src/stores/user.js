import { defineStore } from 'pinia'
import { documentAPI } from '@/api/document'

export const useUserStore = defineStore('user', {
  state: () => ({
    users: [],
    departments: [],
    currentUser: null,
    loading: false,
  }),

  getters: {
    getUsersByRole: (state) => (role) => {
      return state.users.filter(user => user.role === role)
    },
    getDepartmentName: (state) => (id) => {
      const dept = state.departments.find(d => d.id === id)
      return dept ? dept.name : 'N/A'
    },
    canUserHaveDepartment: (state) => (role) => {
      return !['dg', 'file_dept'].includes(role)
    }
  },

  actions: {
    // Fetch all users
    async fetchUsers() {
      this.loading = true
      try {
        const response = await documentAPI.getUsers()
        this.users = response.data.users || []
        return { success: true, data: this.users }
      } catch (error) {
        console.error('Failed to fetch users:', error)
        return { success: false, message: 'Failed to load users' }
      } finally {
        this.loading = false
      }
    },

    // Fetch departments
    async fetchDepartments() {
      try {
        const response = await documentAPI.getDepartmentsList()
        this.departments = response.data.departments || []
        return { success: true, data: this.departments }
      } catch (error) {
        console.error('Failed to fetch departments:', error)
        return { success: false, message: 'Failed to load departments' }
      }
    },

    // Create user
    async createUser(userData) {
      this.loading = true
      try {
        const response = await documentAPI.createUser(userData)
        await this.fetchUsers()
        return { success: true, data: response.data.user }
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to create user'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // Update user
    async updateUser(id, userData) {
      this.loading = true
      try {
        const response = await documentAPI.updateUser(id, userData)
        await this.fetchUsers()
        return { success: true, data: response.data.user }
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to update user'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // Delete user
    async deleteUser(id) {
      this.loading = true
      try {
        await documentAPI.deleteUser(id)
        await this.fetchUsers()
        return { success: true, message: 'User deleted successfully' }
      } catch (error) {
        const message = error.response?.data?.message || 'Failed to delete user'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // Get single user
    async fetchUser(id) {
      this.loading = true
      try {
        const response = await documentAPI.getUser(id)
        this.currentUser = response.data.user
        return { success: true, data: this.currentUser }
      } catch (error) {
        console.error('Failed to fetch user:', error)
        return { success: false, message: 'User not found' }
      } finally {
        this.loading = false
      }
    }
  }
})
