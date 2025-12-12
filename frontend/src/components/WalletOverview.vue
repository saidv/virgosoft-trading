<template>
  <div class="bg-white p-4 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Wallet Overview</h2>
    <div v-if="!authStore.isAuthenticated">
      <p>Please log in to view your wallet.</p>
    </div>
    <div v-else-if="!authStore.currentUser">
      <p>Loading wallet data...</p>
    </div>
    <div v-else>
      <div class="mb-2">
        <p class="text-gray-700 font-medium">USD Balance:</p>
        <p class="text-xl font-bold text-green-600">${{ (parseFloat(authStore.currentUser.balance) || 0).toFixed(2) }}</p>
      </div>
      <div>
        <p class="text-gray-700 font-medium mb-1">Asset Balances:</p>
        <ul v-if="authStore.currentUser.assets && authStore.currentUser.assets.length > 0">
          <li v-for="asset in authStore.currentUser.assets" :key="asset.symbol" class="mb-1">
            {{ asset.symbol }}: {{ (parseFloat(asset.amount) || 0).toFixed(8) }}
            <span v-if="parseFloat(asset.locked_amount) > 0" class="text-sm text-gray-500">(Locked: {{ (parseFloat(asset.locked_amount) || 0).toFixed(8) }})</span>
          </li>
        </ul>
        <p v-else class="text-gray-500">No assets held.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useAuthStore } from '../stores/authStore';

const authStore = useAuthStore();
</script>

<style scoped>
/* Scoped styles for WalletOverview */
</style>
