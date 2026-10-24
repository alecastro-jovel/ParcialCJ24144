import { createRouter, createWebHistory } from 'vue-router'
import { auth } from '../store/auth'

import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import PanelLayout from '../layouts/PanelLayout.vue'
import Dashboard from '../views/Dashboard.vue'
import Registrar from '../views/Registrar.vue'
import Constancias from '../views/Constancias.vue'
import ConstanciaDetalle from '../views/ConstanciaDetalle.vue'
import Informes from '../views/Informes.vue'
import Grafico from '../views/Grafico.vue'
import Verificacion from '../views/Verificacion.vue'
import Usuarios from '../views/Usuarios.vue'

const AMBOS = ['admin', 'registrador']
const SOLO_ADMIN = ['admin']

const routes = [
  { path: '/', name: 'home', component: Home },
  { path: '/login/:rol(admin|registrador)', name: 'login', component: Login, props: true },
  // Login con QR: solo registrador (se llega aquí después de poner bien la contraseña)
  { path: '/verificacion', name: 'verificacion', component: Verificacion },
  {
    path: '/panel',
    component: PanelLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'dashboard', component: Dashboard, meta: { roles: AMBOS } },
      { path: 'registrar', name: 'registrar', component: Registrar, meta: { roles: AMBOS } },
      { path: 'constancias/:estado(activas|inactivas)', name: 'constancias', component: Constancias, props: true, meta: { roles: SOLO_ADMIN } },
      { path: 'constancia/:id(\\d+)', name: 'detalle', component: ConstanciaDetalle, props: true, meta: { roles: SOLO_ADMIN } },
      { path: 'informes', name: 'informes', component: Informes, meta: { roles: AMBOS } },
      { path: 'grafico', name: 'grafico', component: Grafico, meta: { roles: SOLO_ADMIN } },
      { path: 'usuarios', name: 'usuarios', component: Usuarios, meta: { roles: SOLO_ADMIN } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({ history: createWebHistory(), routes })

// Guardia de navegación: protege las rutas por sesión y por rol
router.beforeEach((to) => {
  const logged = auth.isLogged()

  if (to.matched.some((r) => r.meta.requiresAuth) && !logged) return { name: 'home' }
  if (logged && (to.name === 'home' || to.name === 'login' || to.name === 'verificacion')) return { name: 'dashboard' }

  // El paso del QR solo existe justo después del login del registrador
  if (to.name === 'verificacion' && !auth.hayVerificacionPendiente()) {
    return { name: 'login', params: { rol: 'registrador' } }
  }

  // Contraseña temporal: solo se permite el Inicio (donde aparece el cambio de contraseña)
  if (logged && auth.state.user?.must_change_password && to.name !== 'dashboard') return { name: 'dashboard' }

  const roles = to.meta.roles
  if (roles && !roles.includes(auth.role())) return { name: 'dashboard' }
})

export default router
