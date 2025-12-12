<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="login">
        <input type="hidden" name="remember" value="true">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email-address" class="sr-only">Email address</label>
            <input
              id="email-address"
              name="email"
              type="email"
              autocomplete="email"
              required
              v-model="credentials.email"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Email address"
            >
          </div>
          <div>
            <label for="password" class="sr-only">Password</label>
            <input
              id="password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              v-model="credentials.password"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Password"
            >
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="text-sm">
            <router-link to="/register" class="font-medium text-indigo-600 hover:text-indigo-500">
              Don't have an account? Register
            </router-link>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="authStore.loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <!-- Heroicon-s-lock-closed -->
              <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm-4 8H5V9h1V7H5V5a5 5 0 0110 0v2h-1v2h-1V9h1v2a2 2 0 002-2V9a2 2 0 00-2-2H8V5a2 2 0 00-2-2V1z" clip-rule="evenodd" />
              </svg>
            </span>
            Sign in
          </button>
        </div>
        <p v-if="error" class="text-red-500 text-center text-sm mt-4">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import { useToastStore } from '../stores/ToastStore';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const credentials = ref({
  email: '',
  password: '',
});

const error = ref(null);

const login = () => {
  error.value = null; // Clear previous errors
  authStore.login(credentials.value)
    .then(() => {
      toastStore.showToast('Logged in successfully!', 'success');
      nextTick(() => {
        router.replace('/'); // Redirect to dashboard after successful login
      });
    })
    .catch((err) => {
      const message = err.response?.data?.message || err.message || 'Login failed. Please check your credentials.';
      error.value = message;
      toastStore.showToast(message, 'error');
    });
};
</script>

<style scoped>
/* Scoped styles */
</style>
