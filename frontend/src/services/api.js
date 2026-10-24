import axios from 'axios'

// Cliente HTTP único: agrega el token a cada petición
const api = axios.create({
  baseURL: '/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Si el token expiró o es inválido (401) se limpia la sesión y se vuelve al inicio
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401 && !/\/(login|2fa)/.test(err.config.url)) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      window.location.href = '/'
    }
    return Promise.reject(err)
  },
)

export default api
