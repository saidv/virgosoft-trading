<template>
  <div class="bg-white p-4 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Limit Order Form</h2>
    <form @submit.prevent="submitOrder">
      <div class="mb-4">
        <label for="symbol" class="block text-gray-700 text-sm font-bold mb-2">Symbol:</label>
        <select
          id="symbol"
          v-model="order.symbol"
          class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
        >
          <option value="BTC">BTC</option>
          <option value="ETH">ETH</option>
        </select>
      </div>
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2">Side:</label>
        <input type="radio" id="buy" value="buy" v-model="order.side" name="side" class="mr-2">
        <label for="buy" class="mr-4">Buy</label>
        <input type="radio" id="sell" value="sell" v-model="order.side" name="side" class="mr-2">
        <label for="sell">Sell</label>
      </div>
      <div class="mb-4">
        <label for="price" class="block text-gray-700 text-sm font-bold mb-2">Price (USD):</label>
        <input
          type="number"
          id="price"
          v-model.number="order.price"
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          step="0.01"
          min="0"
        >
      </div>
      <div class="mb-4">
        <label for="amount" class="block text-gray-700 text-sm font-bold mb-2">Amount ({{ order.symbol }}):</label>
        <input
          type="number"
          id="amount"
          v-model.number="order.amount"
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          step="0.00000001"
          min="0"
        >
      </div>
      <div v-if="volume" class="mb-4 text-gray-600 text-sm">
        Estimated Volume: ${{ volume.toFixed(2) }}
      </div>
      <button
        type="submit"
        :disabled="orderStore.loading"
        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50"
      >
        {{ orderStore.loading ? 'Placing Order...' : 'Place Order' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useOrderStore } from '../stores/OrderStore';
import { useToastStore } from '../stores/ToastStore';

const orderStore = useOrderStore();
const toastStore = useToastStore();

const order = ref({
  symbol: 'BTC',
  side: 'buy',
  price: 0,
  amount: 0,
});

const volume = computed(() => {
  return order.value.price * order.value.amount;
});

const submitOrder = async () => {
  if (!order.value.symbol || !order.value.side || order.value.price <= 0 || order.value.amount <= 0) {
    toastStore.showToast('Please fill in all order details correctly.', 'warning');
    return;
  }
  try {
    await orderStore.placeOrder(order.value);
    toastStore.showToast('Order placed successfully!', 'success');
    // Optionally reset form
    order.value.price = 0;
    order.value.amount = 0;
  } catch (error) {
    toastStore.showToast(error.message || 'An error occurred while placing the order.', 'error');
  }
};
</script>

<style scoped>
/* Scoped styles for LimitOrderForm */
</style>
