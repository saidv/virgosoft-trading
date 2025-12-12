<script setup>
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();

const handleLogout = () => {
  authStore.logout();
};
</script>

<template>
  <div class="p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Welcome, {{ authStore.authUser?.name }}!</h1>
        <button @click="handleLogout"
                class="px-4 py-2 font-semibold text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
          Logout
        </button>
      </div>
      
      <div class="mt-6 border-t pt-6">
        <h2 class="text-xl font-semibold text-gray-700">Your Details</h2>
        <div v-if="authStore.authUser" class="mt-4 space-y-2">
          <p><span class="font-semibold">Name:</span> {{ authStore.authUser.name }}</p>
          <p><span class="font-semibold">Email:</span> {{ authStore.authUser.email }}</p>
          <p v-if="authStore.authUser.balance"><span class="font-semibold">Balance:</span> ${{ parseFloat(authStore.authUser.balance).toFixed(2) }}</p>
        </div>
      </div>

      <div class="mt-6">
        <p class="text-gray-600">
          This is the main authenticated area. The trading dashboard will be built here.
        </p>
      </div>
    </div>
  </div>
</template>
