import apiClient from './apiClient';

export default {
    /**
     * Fetches all orders for the authenticated user.
     * Assuming an endpoint like /api/my-orders or /api/orders that returns user-specific orders.
     */
    getOrders() {
        return apiClient.get('/orders/my');
    },

    /**
     * Places a new limit order.
     * @param {object} orderData - The order details (symbol, side, price, amount).
     */
    placeOrder(orderData) {
        return apiClient.post('/orders', orderData);
    },

    /**
     * Cancels an existing open order.
     * @param {string|number} orderId - The ID of the order to cancel.
     */
    cancelOrder(orderId) {
        return apiClient.post(`/orders/${orderId}/cancel`);
    },

    /**
     * Fetches the public order book for a given symbol.
     * @param {string} symbol - The trading pair symbol (e.g., 'BTC', 'ETH').
     */
    getOrderBook(symbol) {
        return apiClient.get(`/orders?symbol=${symbol}`);
    }
};
