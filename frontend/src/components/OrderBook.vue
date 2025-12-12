<template>
  <div class="bg-white p-4 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Order Book</h2>

    <div class="flex space-x-4 mb-4">
      <label for="order-book-symbol" class="block text-gray-700 text-sm font-bold my-auto">Symbol:</label>
      <select
        id="order-book-symbol"
        v-model="selectedSymbol"
        @change="fetchOrderBook"
        class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
      >
        <option value="BTC">BTC</option>
        <option value="ETH">ETH</option>
      </select>
    </div>

    <div v-if="orderStore.orderBookLoading" class="text-center py-4">Loading order book...</div>
    <div v-else-if="orderStore.orderBookError" class="text-red-500 py-4">Error: {{ orderStore.orderBookError.message }}</div>
    <div v-else class="grid grid-cols-2 gap-4">
      <div>
        <h3 class="text-lg font-medium mb-2 text-green-600">Buy Orders (Bids)</h3>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (USD)</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount ({{ selectedSymbol }})</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="orderStore.orderBook.buy.length === 0">
              <td colspan="2" class="px-6 py-4 text-center text-gray-500">No buy orders.</td>
            </tr>
            <tr v-for="order in orderStore.orderBook.buy" :key="order.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ parseFloat(order.price).toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ parseFloat(order.amount).toFixed(8) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h3 class="text-lg font-medium mb-2 text-red-600">Sell Orders (Asks)</h3>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (USD)</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount ({{ selectedSymbol }})</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="orderStore.orderBook.sell.length === 0">
              <td colspan="2" class="px-6 py-4 text-center text-gray-500">No sell orders.</td>
            </tr>
            <tr v-for="order in orderStore.orderBook.sell" :key="order.id">
              <td class="px-6 py-4 whitespace-nowrap">{{ parseFloat(order.price).toFixed(2) }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ parseFloat(order.amount).toFixed(8) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useOrderStore } from '../stores/OrderStore';

const orderStore = useOrderStore();
const selectedSymbol = ref('BTC');

const fetchOrderBook = () => {
  orderStore.fetchOrderBook(selectedSymbol.value);
};

onMounted(() => {
  fetchOrderBook();
});

watch(selectedSymbol, () => {
  fetchOrderBook();
});
</script>

<style scoped>
/* Scoped styles for OrderBook */
</style>
