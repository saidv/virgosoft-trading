<script setup>
import { ref, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();

const email = ref('');
const password = ref('');

const handleLogin = async () => {
  await authStore.login({
    email: email.value,
    password: password.value,
  });
};

// Clear errors when component is unmounted
onUnmounted(() => {
  authStore.clearErrors();
});
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center text-gray-900">Sign in to your account</h2>
      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
          <input v-model="email" id="email" type="email" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input v-model="password" id="password" type="password" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        
        <div v-if="authStore.errors.credentials" class="text-sm text-red-600">
          {{ authStore.errors.credentials[0] }}
        </div>
        <div v-if="authStore.errors.general" class="text-sm text-red-600">
          {{ authStore.errors.general[0] }}
        </div>

        <div class="flex items-center justify-between">
          <div class="text-sm">
            <router-link :to="{ name: 'forgot-password' }" class="font-medium text-indigo-600 hover:text-indigo-500">
              Forgot your password?
            </router-link>
          </div>
        </div>

        <div>
          <button type="submit" :disabled="authStore.isLoading"
                  class="w-full px-4 py-2 font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300">
            <span v-if="authStore.isLoading">Signing in...</span>
            <span v-else>Sign in</span>
          </button>
        </div>
      </form>
       <p class="text-sm text-center text-gray-600">
          Don't have an account?
          <router-link :to="{ name: 'register' }" class="font-medium text-indigo-600 hover:text-indigo-500">
            Sign up
          </router-link>
        </p>
    </div>
  </div>
</template>
