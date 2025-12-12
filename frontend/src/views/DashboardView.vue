<template>
  <div class="bg-gray-100 min-h-screen">
    <Header />
    <div class="p-4">
      <h1 class="text-3xl font-bold mb-6 text-gray-800">Trading Dashboard</h1>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <WalletOverview />
        <LimitOrderForm />
        <!-- Placeholder for other dashboard elements or an empty slot if needed -->
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <OrderHistory />
        <OrderBook />
      </div>
    </div>
  </div>
</template>

<script setup>
import Header from '../components/Header.vue';
import WalletOverview from '../components/WalletOverview.vue';
import LimitOrderForm from '../components/LimitOrderForm.vue';
import OrderHistory from '../components/OrderHistory.vue';
import OrderBook from '../components/OrderBook.vue';
</script>

<style scoped>
/* Scoped styles for DashboardView */
</style>
