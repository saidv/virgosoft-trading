<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import authService from '../services/authService';

const authStore = useAuthStore();
const email = ref('');
const message = ref('');
const isLoading = ref(false);

const handleForgotPassword = async () => {
  message.value = '';
  isLoading.value = true;
  try {
    const response = await authService.forgotPassword(email.value);
    message.value = response.message || 'If an account with that email exists, a password reset link has been sent.';
  } catch (error) {
    message.value = 'If an account with that email exists, a password reset link has been sent.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-bold text-center text-gray-900">Forgot Your Password?</h2>
      <p class="text-sm text-center text-gray-600">
        Enter your email address and we will send you a link to reset your password.
      </p>
      <div v-if="message" class="p-4 text-sm text-green-700 bg-green-100 border border-green-400 rounded-md">
        {{ message }}
      </div>
      <form v-else @submit.prevent="handleForgotPassword" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
          <input v-model="email" id="email" type="email" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>

        <div>
          <button type="submit" :disabled="isLoading"
                  class="w-full px-4 py-2 font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300">
            <span v-if="isLoading">Sending...</span>
            <span v-else>Send Password Reset Link</span>
          </button>
        </div>
      </form>
       <p class="text-sm text-center text-gray-600">
          Remember your password?
          <router-link :to="{ name: 'login' }" class="font-medium text-indigo-600 hover:text-indigo-500">
            Sign in
          </router-link>
        </p>
    </div>
  </div>
</template>
