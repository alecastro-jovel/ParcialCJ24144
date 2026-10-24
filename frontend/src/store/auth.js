import { reactive } from 'vue'
import api from '../services/api'

// Estado de sesión compartido (sin Pinia para mantenerlo simple)
const state = reactive({
  token: localStorage.getItem('token'),
  user: JSON.parse(localStorage.getItem('user') || 'null'),
})

// "Ticket" temporal del registrador mientras confirma el código del QR (se pierde al cerrar la pestaña)
const cabeceraTicket = () => ({ headers: { 'X-Login-Ticket': sessionStorage.getItem('login_ticket') ?? '' } })

function guardarSesion(data) {
  state.token = data.token
  state.user = data.user
  localStorage.setItem('token', data.token)
  localStorage.setItem('user', JSON.stringify(data.user))
  sessionStorage.removeItem('login_ticket')
  sessionStorage.removeItem('login_email')
}

export const auth = {
  state,
  isLogged: () => !!state.token,
  role: () => state.user?.role,
  hayVerificacionPendiente: () => !!sessionStorage.getItem('login_ticket'),
  correoPendiente: () => sessionStorage.getItem('login_email') ?? '',

  /**
   * Paso 1. Administrador: entra directo.
   * Registrador: devuelve { requires2fa: true } y guarda el ticket para el paso del QR.
   */
  async login(rol, email, password) {
    const { data } = await api.post(`/login/${rol}`, { email, password })
    if (data.requires_2fa) {
      sessionStorage.setItem('login_ticket', data.ticket)
      sessionStorage.setItem('login_email', data.email)
      return { requires2fa: true }
    }
    guardarSesion(data)
    return { requires2fa: false }
  },

  /** Paso 2 (solo registrador): pide el QR (solo viene la primera vez) */
  async generarQr() {
    const { data } = await api.get('/2fa/generate', cabeceraTicket())
    return data
  },

  /** Paso 2 (solo registrador): valida el código de 6 dígitos y abre la sesión */
  async verificarCodigo(code) {
    const { data } = await api.post('/2fa/verify', { code }, cabeceraTicket())
    guardarSesion(data)
  },

  cancelarVerificacion() {
    sessionStorage.removeItem('login_ticket')
    sessionStorage.removeItem('login_email')
  },

  setUser(user) {
    state.user = user
    localStorage.setItem('user', JSON.stringify(user))
  },

  async logout() {
    try { await api.post('/logout') } catch (e) { /* si falla igual cerramos */ }
    state.token = null
    state.user = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  },
}
