<template>
  <div class="bg-white p-4 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Order History</h2>

    <div v-if="orderStore.error" class="text-red-500 py-4">Error: {{ orderStore.error.message }}</div>
    <div v-if="orderStore.loading" class="text-center py-4">Loading orders...</div>
    <div v-else>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Symbol</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Side</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="!orderStore.orders || orderStore.orders.length === 0">
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No orders found.</td>
          </tr>
          <tr v-for="order in orderStore.orders.filter(o => o)" :key="order.id">
            <td class="text-center py-4 whitespace-nowrap">{{ order.symbol }}</td>
            <td class="text-center py-4 whitespace-nowrap">{{ order.side?.toUpperCase() }}</td>
            <td class="text-center py-4 whitespace-nowrap">{{ Number(order.price).toFixed(2) }}</td>
            <td class="text-center py-4 whitespace-nowrap">{{ Number(order.amount).toFixed(2) }}</td>
            <td class="text-center py-4 whitespace-nowrap">
              <span
                :class="{
                  'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                  'bg-green-100 text-green-800': order.status === 2, // Filled
                  'bg-yellow-100 text-yellow-800': order.status === 1, // Open
                  'bg-red-100 text-red-800': order.status === 3, // Cancelled
                }"
              >
                {{ getStatusText(order.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <button
                v-if="order.status === 1"
                @click="confirmCancelOrder(order.id)"
                class="text-red-600 hover:text-red-900 font-medium"
                :disabled="orderStore.loading"
              >
                Cancel
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useOrderStore } from '../stores/OrderStore';
import { useToastStore } from '../stores/ToastStore'; // Import useToastStore

const orderStore = useOrderStore();
const toastStore = useToastStore(); // Initialize toastStore

onMounted(() => {
  orderStore.fetchOrders();
});

const getStatusText = (status) => {
  switch (status) {
    case 1: return 'Open';
    case 2: return 'Filled';
    case 3: return 'Cancelled';
    default: return 'Unknown';
  }
};

const confirmCancelOrder = (orderId) => {
  // Using toast for confirmation as well, or you could use a modal component
  toastStore.showToast('Cancelling order...', 'info', 2000); // Show immediate feedback
  cancelOrder(orderId);
};

const cancelOrder = async (orderId) => {
  try {
    await orderStore.cancelOrder(orderId);
    toastStore.showToast('Order cancelled successfully!', 'success');
  } catch (error) {
    toastStore.showToast(error.message || 'An error occurred while cancelling the order.', 'error');
  }
};
</script>

<style scoped>
/* Scoped styles for OrderHistory */
</style>
