<template>
  <header class="bg-gray-800 text-white p-4 shadow-md flex justify-between items-center">
    <div class="text-xl font-semibold">
      <span v-if="authStore.currentUser">Hello, {{ authStore.currentUser.name }}</span>
      <span v-else>Welcome</span>
    </div>
    <nav>
      <button
        @click="logout"
        class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
      >
        Logout
      </button>
    </nav>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { nextTick } from 'vue';
import { useAuthStore } from '../stores/AuthStore';
import { useToastStore } from '../stores/ToastStore';

const router = useRouter();
const authStore = useAuthStore();
const toastStore = useToastStore();

const logout = async () => {
  try {
    await authStore.logout();
    toastStore.showToast('Logged out successfully!', 'success');
    nextTick(() => {
      router.push('/login'); // Explicitly redirect to login page
    });
  } catch (error) {
    toastStore.showToast('Failed to logout: ' + (error.message || 'An error occurred.'), 'error');
  }
};
</script>

<style scoped>
/* Add any scoped styles here if needed */
</style>
