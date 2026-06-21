
import { defineStore } from 'pinia'
import { documentAPI } from '@/api/document'

export const useDocumentStore = defineStore('document', {
  state: () => ({
    documents: [],
    urgentDocuments: [],
    inboxDocuments: [],
    archiveDocuments: [],
    departments: [], // Added for department dropdown
    currentDocument: null,
    loading: false,
    pagination: {
      page: 1,
      limit: 20,
      total: 0,
    },
  }),

  getters: {
    // Get documents by status
    getByStatus: (state) => (status) => {
      return state.documents.filter(doc => doc.status === status)
    },

    // Count pending documents
    pendingCount: (state) => {
      return state.documents.filter(doc =>
        ['pending_dg_init', 'pending_dispatch', 'pending_vdg_approval', 'pending_dg_approval'].includes(doc.status)
      ).length
    },

    // Get document by ID
    getById: (state) => (id) => {
      return state.documents.find(doc => doc.id === id)
    },

    // Safe getter for archive documents
    archiveList: (state) => {
      return state.archiveDocuments || []
    },

    // Get department name by ID
    getDepartmentName: (state) => (id) => {
      const dept = state.departments.find(d => d.id === id)
      return dept ? dept.name : 'Unknown'
    }
  },

  actions: {
    // ===================== DOCUMENT CRUD =====================

    // GET /api/documents
    async fetchDocuments() {
      this.loading = true
      try {
        const response = await documentAPI.getDocuments()
        this.documents = response.data.documents || []
        this.pagination.total = this.documents.length
        return response.data
      } catch (error) {
        console.error('Failed to fetch documents:', error)
        return null
      } finally {
        this.loading = false
      }
    },

    // GET /api/documents/{id}
    async fetchDocument(id) {
      this.loading = true
      try {
        const response = await documentAPI.getDocument(id)
        this.currentDocument = response.data.document
        return response.data
      } catch (error) {
        console.error('Failed to fetch document:', error)
        return null
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 1: UPLOAD =====================
    // POST /api/documents (file_dept only)
    async uploadDocument(formData) {
      this.loading = true
      try {
        const response = await documentAPI.upload(formData)
        await this.fetchDocuments()
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Upload failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 2: DG ASSIGN =====================
    // POST /api/documents/{id}/direct (DG only)
    async assignDocument(id, data) {
      this.loading = true
      try {
        const response = await documentAPI.assign(id, data)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Assignment failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 3: DISPATCH =====================
    // POST /api/documents/{id}/dispatch (file_dept only)
    async dispatchDocument(id, data) {
      this.loading = true
      try {
        const response = await documentAPI.dispatch(id, data)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Dispatch failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 4: UPLOAD REPORT =====================
    // POST /api/documents/{id}/report (dept/staff only)
    async uploadReport(id, formData) {
      this.loading = true
      try {
        const response = await documentAPI.report(id, formData)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Report upload failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 5: VDG SIGN =====================
    // POST /api/documents/{id}/vdg-sign (VDG only)
    async signVDG(id) {
      this.loading = true
      try {
        const response = await documentAPI.vdgSign(id)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'VDG signature failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 6: DG SIGN =====================
    // POST /api/documents/{id}/dg-sign (DG only)
    async signDG(id) {
      this.loading = true
      try {
        const response = await documentAPI.dgSign(id)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'DG signature failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== PHASE 7: ARCHIVE =====================
    // POST /api/documents/{id}/archive (file_dept only)
    async archiveDocument(id) {
      this.loading = true
      try {
        const response = await documentAPI.archive(id)
        await this.fetchDocuments()
        await this.fetchDocument(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Archive failed'
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== DASHBOARD FEEDS =====================

    // GET /api/documents/urgent (All roles)
    async fetchUrgent() {
      this.loading = true
      try {
        const response = await documentAPI.getUrgent()
        this.urgentDocuments = response.data.documents || []
        return response.data
      } catch (error) {
        console.error('Failed to fetch urgent:', error)
        return null
      } finally {
        this.loading = false
      }
    },

    // GET /api/departments/inbox (dept/staff/vdg)
    async fetchInbox() {
      this.loading = true
      try {
        const response = await documentAPI.getInbox()
        this.inboxDocuments = response.data.documents || []
        return response.data
      } catch (error) {
        console.error('Failed to fetch inbox:', error)
        return null
      } finally {
        this.loading = false
      }
    },

    // ===================== DEPARTMENTS =====================

    // GET /api/departments (All roles)
    async fetchDepartments() {
      try {
        const response = await documentAPI.getDepartments()
        this.departments = response.data.departments || []
        return response.data
      } catch (error) {
        console.error('Failed to fetch departments:', error)
        return null
      }
    },

    // ===================== ARCHIVE SEARCH =====================

    // GET /api/documents/archive?search=X (All roles)
    async searchArchive(query) {
      this.loading = true
      try {
        const response = await documentAPI.searchArchive(query)
        this.archiveDocuments = response.data.documents || []
        this.pagination.total = response.data.result_count || 0
        return {
          success: true,
          data: response.data,
          accessLevel: response.data.access_level || 'Restricted'
        }
      } catch (error) {
        const message = error.response?.data?.message || 'Search failed'
        this.archiveDocuments = []
        return { success: false, message }
      } finally {
        this.loading = false
      }
    },

    // ===================== DOWNLOAD =====================

    // GET /api/documents/{id}/download (All roles)
    async downloadDocument(id) {
      try {
        const response = await documentAPI.download(id)
        return { success: true, data: response.data }
      } catch (error) {
        const message = error.response?.data?.message || 'Download failed'
        return { success: false, message }
      }
    },

    // ===================== CLEAR FUNCTIONS =====================

    clearCurrentDocument() {
      this.currentDocument = null
    },

    clearArchive() {
      this.archiveDocuments = []
    },

    // Reset all state
    resetState() {
      this.documents = []
      this.urgentDocuments = []
      this.inboxDocuments = []
      this.archiveDocuments = []
      this.departments = []
      this.currentDocument = null
      this.loading = false
      this.pagination = {
        page: 1,
        limit: 20,
        total: 0,
      }
    }
  }
})
