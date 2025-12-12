<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Create a new account
        </h2>
      </div>
      <form class="mt-8 space-y-6" @submit.prevent="register">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="name" class="sr-only">Name</label>
            <input
              id="name"
              name="name"
              type="text"
              autocomplete="name"
              required
              v-model="credentials.name"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Full Name"
            >
          </div>
          <div>
            <label for="register-email-address" class="sr-only">Email address</label>
            <input
              id="register-email-address"
              name="email"
              type="email"
              autocomplete="email"
              required
              v-model="credentials.email"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Email address"
            >
          </div>
          <div>
            <label for="register-password" class="sr-only">Password</label>
            <input
              id="register-password"
              name="password"
              type="password"
              autocomplete="new-password"
              required
              v-model="credentials.password"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Password"
            >
          </div>
          <div>
            <label for="password-confirm" class="sr-only">Confirm Password</label>
            <input
              id="password-confirm"
              name="password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              v-model="credentials.password_confirmation"
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
              placeholder="Confirm Password"
            >
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="text-sm">
            <router-link to="/" class="font-medium text-indigo-600 hover:text-indigo-500">
              Already have an account? Sign in
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
              <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm-4 8H5V9h1V7H5V5a2 2 0 00-2-2V1z" clip-rule="evenodd" />
              </svg>
            </span>
            Register
          </button>
        </div>
        <p v-if="error" class="text-red-500 text-center text-sm mt-4">{{ error }}</p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/AuthStore';
import { useToastStore } from '../stores/ToastStore';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const credentials = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const error = ref(null);

const register = async () => {
  error.value = null; // Clear previous errors
  try {
    if (credentials.value.password !== credentials.value.password_confirmation) {
      error.value = 'Passwords do not match.';
      toastStore.showToast(error.value, 'error');
      return;
    }
    await authStore.register(credentials.value);
    toastStore.showToast('Registration successful! Redirecting to dashboard...', 'success');
    router.push('/'); // Redirect to dashboard after successful registration
  } catch (err) {
    error.value = err.response?.data?.message || 'Registration failed.';
    toastStore.showToast(error.value, 'error');
  }
};
</script>

<style scoped>
/* Scoped styles */
</style>
