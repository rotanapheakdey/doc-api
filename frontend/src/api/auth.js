import api from './axios'

export const authAPI = {
  // POST /api/login
  login: (email, password) =>
    api.post('/login', { email, password }),

  // POST /api/logout
  logout: () =>
    api.post('/logout'),

  // GET /api/user (if available)
  getUser: () =>
    api.get('/user'),
}

