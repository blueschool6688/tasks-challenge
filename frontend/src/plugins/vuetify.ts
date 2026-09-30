import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'

export default createVuetify({
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          primary: '#2563EB',     // Modern Crisp Indigo/Blue
          secondary: '#475569',   // Slate 600
          accent: '#3B82F6',
          error: '#EF4444',       // Rose Red
          info: '#0EA5E9',        // Sky Blue
          success: '#10B981',     // Emerald Green
          warning: '#F59E0B',     // Amber
          background: '#F8FAFC',  // Clean subtle light slate canvas
          surface: '#FFFFFF',
        },
      },
    },
  },
})
