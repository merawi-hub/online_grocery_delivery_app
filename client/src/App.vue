<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './components/Sidebar.vue';
import HeaderBar from './components/HeaderBar.vue';
import AddressModal from './components/AddressModal.vue';
import { useAuthStore } from './stores/auth';
import { useAddressStore } from './stores/address';

const route = useRoute();
const authStore = useAuthStore();
const addressStore = useAddressStore();
const isAddressModalOpen = ref(false);

const isAuthPage = computed(() => {
  return route.path === '/login';
});

function openAddressModal() {
  isAddressModalOpen.value = true;
}

function closeAddressModal() {
  isAddressModalOpen.value = false;
}

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    // Start with customer for initial load
    await authStore.login('+251911000005', 'password123');
  }
  if (authStore.isAuthenticated) {
    await addressStore.fetchAddresses();
  }
});
</script>

<template>
  <!-- Full Screen for Authentication Page -->
  <div v-if="isAuthPage" class="auth-only-container">
    <router-view />
  </div>

  <!-- Enterprise Dashboard Shell for All Application Pages -->
  <div v-else class="app-shell">
    <!-- Clean, Professional Sidebar -->
    <Sidebar @open-address-modal="openAddressModal" />

    <!-- Main Content Viewport -->
    <div class="main-viewport">
      <!-- Clean Top Bar -->
      <HeaderBar @open-address-modal="openAddressModal" />

      <!-- Active Page Route -->
      <main class="content-area">
        <router-view @open-address-modal="openAddressModal" />
      </main>
    </div>

    <!-- Precision Address Modal (GPS Coordinates + Building Drop Details) -->
    <AddressModal
      :is-open="isAddressModalOpen"
      @close="closeAddressModal"
    />
  </div>
</template>

<style scoped>
.auth-only-container {
  width: 100vw;
  min-height: 100vh;
}
</style>
