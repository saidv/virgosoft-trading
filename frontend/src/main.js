import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

// Create app instance
const app = createApp(App)

// Register plugins
app.use(createPinia())
app.use(router)

// Initialize authentication state
import { useAuthStore } from './stores/AuthStore';
const authStore = useAuthStore();
authStore.initializeAuth();

// Mount app
app.mount('#app')
