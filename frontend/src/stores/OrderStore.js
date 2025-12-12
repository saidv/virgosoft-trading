import { defineStore } from 'pinia';
import orderService from '../services/orderService';
import { useToastStore } from './ToastStore';

export const useOrderStore = defineStore('order', {
  state: () => ({
    orders: [],
    loading: false,
    error: null,
    orderBook: {
      buy: [],
      sell: [],
    },
    orderBookLoading: false,
    orderBookError: null,
  }),
  actions: {
    async fetchOrders() {
      this.loading = true;
      this.error = null;
      try {
        const response = await orderService.getOrders();
        // Handle paginated response structure: response.data.data.data
        if (response.data.data && Array.isArray(response.data.data.data)) {
            this.orders = response.data.data.data;
        } else if (Array.isArray(response.data.data)) {
            // Fallback for non-paginated array
            this.orders = response.data.data;
        } else {
            this.orders = [];
        }
      } catch (error) {
        this.error = error.response?.data || { message: 'Failed to fetch orders.' };
        this.orders = []; // Also clear orders on error
      } finally {
        this.loading = false;
      }
    },
    async placeOrder(orderData) {
      this.loading = true;
      this.error = null;
      try {
        const response = await orderService.placeOrder(orderData);
        // Refresh both user's orders and the public order book
        this.fetchOrders();
        this.fetchOrderBook(orderData.symbol); 
        return response;
      } catch (error) {
        this.error = error.response?.data || { message: 'Failed to place order.' };
        throw this.error; // Re-throw the extracted error for the component to catch
      } finally {
        this.loading = false;
      }
    },
    async cancelOrder(orderId) {
      this.loading = true;
      this.error = null;
      try {
        await orderService.cancelOrder(orderId);
        this.orders = this.orders.map(order => 
          order.id === orderId ? { ...order, status: 3 } : order
        );
      } catch (error) {
        this.error = error.response?.data || { message: 'Failed to cancel order.' };
        throw this.error; // Re-throw the extracted error for the component to catch
      }
      finally {
        this.loading = false;
      }
    },
    async fetchOrderBook(symbol) {
      this.orderBookLoading = true;
      this.orderBookError = null;
      try {
        const response = await orderService.getOrderBook(symbol);
        const data = response.data.data;
        
        // Check if the response has the structured buy_orders/sell_orders format
        if (data && (data.buy_orders || data.sell_orders)) {
            this.orderBook.buy = data.buy_orders || [];
            this.orderBook.sell = data.sell_orders || [];
        } else if (Array.isArray(data)) {
            // Fallback for flat array structure
            this.orderBook.buy = data.filter(order => order.side === 'buy');
            this.orderBook.sell = data.filter(order => order.side === 'sell');
        } else {
            this.orderBook.buy = [];
            this.orderBook.sell = [];
        }
      } catch (error) {
        this.orderBookError = error.response?.data || { message: 'Failed to fetch order book.' };
        this.orderBook.buy = [];
        this.orderBook.sell = [];
      } finally {
        this.orderBookLoading = false;
      }
    },
    // Action to handle OrderMatched event from Pusher
    async handleOrderMatched(data) {
        console.log('OrderStore: OrderMatched event received, refreshing data:', data);
        
        // Show toast notification
        const toastStore = useToastStore();
        const symbol = data.trade?.symbol || 'Unknown';
        const amount = data.trade?.amount || '0';
        const price = data.trade?.price || '0';
        
        toastStore.showToast(
            `Order Matched! ${amount} ${symbol} @ ${price}`,
            'success',
            5000
        );

        // Refetch user's orders to update their status and history
        await this.fetchOrders();
        
        // Refetch order book for the matched symbol to reflect changes
        if (data.trade?.symbol) {
            await this.fetchOrderBook(data.trade.symbol);
        } else if (data.symbol) {
             // Fallback if data structure is different
            await this.fetchOrderBook(data.symbol);
        }
    }
  },
});
