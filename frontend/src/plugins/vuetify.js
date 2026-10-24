import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

// Estándar de colores
export default createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#313945',    // Primario
          secondary: '#2D52A8',  // Secundario
          background: '#F4F7FD', // Fondo pantallas
          surface: '#FFFFFF',    // Fondo modales y cards
          error: '#EF5350',      // Error
          warning: '#FFEAA7',    // Observaciones, advertencia
          success: '#57CC99',    // Éxito
          info: '#2D52A8',
        },
      },
    },
  },
})
