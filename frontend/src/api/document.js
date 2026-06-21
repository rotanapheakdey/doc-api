import api from './axios'

export const documentAPI = {
  // ===================== BACKEND: GET /documents =====================
  getDocuments: () =>
    api.get('/documents'),

  // ===================== BACKEND: GET /documents/{id} =====================
  getDocument: (id) =>
    api.get(`/documents/${id}`),

  // ===================== BACKEND: POST /documents =====================
  upload: (formData) =>
    api.post('/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // ===================== BACKEND: POST /documents/{id}/direct =====================
  assign: (id, data) =>
    api.post(`/documents/${id}/direct`, data),

  // ===================== BACKEND: POST /documents/{id}/dispatch =====================
  dispatch: (id, data) =>
    api.post(`/documents/${id}/dispatch`, data),

  // ===================== BACKEND: POST /documents/{id}/report =====================
  report: (id, formData) =>
    api.post(`/documents/${id}/report`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // ===================== BACKEND: POST /documents/{id}/vdg-sign =====================
  vdgSign: (id, data = {}) =>
    api.post(`/documents/${id}/vdg-sign`, data),

  // ===================== BACKEND: POST /documents/{id}/dg-sign =====================
  dgSign: (id, data = {}) =>
    api.post(`/documents/${id}/dg-sign`, data),

  // ===================== BACKEND: POST /documents/{id}/archive =====================
  archive: (id, data = {}) =>
    api.post(`/documents/${id}/archive`, data),

  // ===================== BACKEND: GET /documents/urgent =====================
  getUrgent: () =>
    api.get('/documents/urgent'),

  // ===================== BACKEND: GET /departments/inbox =====================
  getInbox: () =>
    api.get('/departments/inbox'),

  // ===================== BACKEND: GET /departments =====================
  getDepartments: () =>
    api.get('/departments'),

  // ===================== BACKEND: GET /documents/archive =====================
  searchArchive: (query) =>
    api.get('/documents/archive', { params: { search: query } }),

  // ===================== BACKEND: GET /documents/{id}/download =====================
  download: (id) =>
    api.get(`/documents/${id}/download`, { responseType: 'blob' }),

  // ===================== USERS =====================
  getUsers: () => api.get('/users'),
  getUser: (id) => api.get(`/users/${id}`),
  createUser: (data) => api.post('/users', data),
  updateUser: (id, data) => api.put(`/users/${id}`, data),
  deleteUser: (id) => api.delete(`/users/${id}`),
  getDepartmentsList: () => api.get('/departments/list'),
}
