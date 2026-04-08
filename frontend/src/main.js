import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import AOS from 'aos'
import 'aos/dist/aos.css'

const app = createApp(App)

app.use(router)
app.use(store)

// Check for corrupted auth state on startup
store.dispatch('auth/checkAuth')

// Initialize AOS
app.mixin({
  mounted() {
    AOS.init({
      duration: 1000,
      once: true
    })
  }
})

app.mount('#app')