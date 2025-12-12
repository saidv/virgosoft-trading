import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/authStore'; // Import AuthStore

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }, // Mark this route as requiring authentication
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue'),
    meta: { requiresAuth: false }, // Login page does not require authentication
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/RegisterView.vue'),
    meta: { requiresAuth: false }, // Register page does not require authentication
  },
  // Catch-all route for 404s
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    redirect: '/', // Redirect to dashboard or a dedicated 404 page
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;

  // If the route requires authentication and the user is not authenticated, redirect to login
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login');
  }
  // If the route does not require authentication (login/register) and the user is already authenticated, redirect to dashboard
  else if (!to.meta.requiresAuth && isAuthenticated) {
    next('/');
  }
  // Otherwise, proceed to the route
  else {
    next();
  }
});

export default router;