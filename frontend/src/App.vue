<script setup>
import ToastNotification from './components/ToastNotification.vue';
// Main app component - RouterView handles all routing
</script>

<template>
  <div class="min-h-screen bg-gray-50 font-sans antialiased container mx-auto">
    <RouterView />
    <ToastNotification />
  </div>
</template>

<style>
/* Global styles are in style.css using Tailwind */
</style>
