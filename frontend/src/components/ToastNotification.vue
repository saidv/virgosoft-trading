<template>
  <Transition
    enter-active-class="transition ease-out duration-300 transform"
    enter-from-class="opacity-0 translate-y-full"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200 transform"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 translate-y-full"
  >
    <div
      v-if="toastStore.isVisible"
      :class="['fixed bottom-4 right-4 p-4 rounded-lg shadow-lg text-white max-w-sm z-50', toastClass]"
    >
      <div class="flex items-center justify-between">
        <p class="font-medium">{{ toastStore.message }}</p>
        <button @click="toastStore.hideToast()" class="ml-4 focus:outline-none">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { useToastStore } from '../stores/ToastStore';

const toastStore = useToastStore();

const toastClass = computed(() => {
  switch (toastStore.type) {
    case 'success':
      return 'bg-green-500';
    case 'error':
      return 'bg-red-500';
    case 'warning':
      return 'bg-yellow-500';
    default:
      return 'bg-blue-500'; // info
  }
});
</script>

<style scoped>
</style>
