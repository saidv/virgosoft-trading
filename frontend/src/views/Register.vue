<script setup>
import { ref, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();

const name = ref('');
const email = ref('');
const password = ref('');
const password_confirmation = ref('');

const handleRegister = async () => {
  await authStore.register({
    name: name.value,
    email: email.value,
    password: password.value,
    password_confirmation: password_confirmation.value
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
      <h2 class="text-2xl font-bold text-center text-gray-900">Create an Account</h2>
      <form @submit.prevent="handleRegister" class="space-y-6">
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
          <input v-model="name" id="name" type="text" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
          <p v-if="authStore.errors.name" class="mt-2 text-sm text-red-600">{{ authStore.errors.name[0] }}</p>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
          <input v-model="email" id="email" type="email" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
          <p v-if="authStore.errors.email" class="mt-2 text-sm text-red-600">{{ authStore.errors.email[0] }}</p>
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
          <input v-model="password" id="password" type="password" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
          <p v-if="authStore.errors.password" class="mt-2 text-sm text-red-600">{{ authStore.errors.password[0] }}</p>
        </div>
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
          <input v-model="password_confirmation" id="password_confirmation" type="password" required
                 class="w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" />
        </div>
        <div>
          <button type="submit" :disabled="authStore.isLoading"
                  class="w-full px-4 py-2 font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-indigo-300">
            <span v-if="authStore.isLoading">Registering...</span>
            <span v-else>Register</span>
          </button>
        </div>
        <div v-if="authStore.errors.general" class="text-center text-sm text-red-600">
          {{ authStore.errors.general[0] }}
        </div>
      </form>
       <p class="text-sm text-center text-gray-600">
          Already have an account?
          <router-link :to="{ name: 'login' }" class="font-medium text-indigo-600 hover:text-indigo-500">
            Sign in
          </router-link>
        </p>
    </div>
  </div>
</template>
