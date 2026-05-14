import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
})

// You can add interceptors here later (e.g., for automatic token handling)
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  const sessionId = localStorage.getItem('session_id')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  if (sessionId) {
    config.headers['X-Session-ID'] = sessionId
  }

  return config
})

export default api
