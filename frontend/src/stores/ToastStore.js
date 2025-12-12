import { defineStore } from 'pinia';

export const useToastStore = defineStore('toast', {
  state: () => ({
    message: '',
    type: 'info', // 'info', 'success', 'warning', 'error'
    isVisible: false,
    timeoutId: null,
  }),
  actions: {
    showToast(message, type = 'info', duration = 3000) {
      this.message = message;
      this.type = type;
      this.isVisible = true;

      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }
      this.timeoutId = setTimeout(() => {
        this.hideToast();
      }, duration);
    },
    hideToast() {
      this.isVisible = false;
      this.message = '';
      this.type = 'info';
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
        this.timeoutId = null;
      }
    },
  },
});
