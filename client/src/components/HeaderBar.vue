<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';
import { useAddressStore } from '../stores/address';

const emit = defineEmits(['open-address-modal']);

const route = useRoute();
const authStore = useAuthStore();
const cartStore = useCartStore();
const addressStore = useAddressStore();

const pageTitle = computed(() => {
  if (route.path === '/') return 'Storefront Catalog & Fresh Aisles';
  if (route.path === '/cart') return 'Grocery Basket & Checkout';
  if (route.path.startsWith('/tracking')) return 'Live GPS Radar Tracking';
  if (route.path === '/picker') return 'In-Store Order Fulfillment Station';
  if (route.path === '/driver') return 'Courier Dispatch & Turn-by-Turn Navigation';
  return 'FreshSupermarket Dashboard';
});

const activeAddressSummary = computed(() => {
  const addr = addressStore.activeAddress;
  if (!addr) return 'Set delivery address';
  return `${addr.building_name_or_no || addr.street_name || 'Home'}, #${addr.house_villa_no}`;
});
</script>

<template>
  <header class="topbar">
    <div class="topbar-left">
      <h1 class="topbar-title">{{ pageTitle }}</h1>
    </div>

    <div class="topbar-right">
      <!-- Address pill for customer -->
      <div
        v-if="authStore.userRole === 'customer'"
        class="address-btn"
        @click="emit('open-address-modal')"
        title="Change delivery location"
      >
        <span class="pin">📍</span>
        <div class="addr-meta">
          <small>Deliver to</small>
          <strong class="truncate">{{ activeAddressSummary }}</strong>
        </div>
        <span class="chevron">▼</span>
      </div>

      <!-- Quick Cart shortcut -->
      <router-link
        v-if="authStore.userRole === 'customer'"
        to="/cart"
        class="cart-shortcut"
        :class="{ has_items: cartStore.totalCount > 0 }"
      >
        <span>🛍️ Basket</span>
        <span v-if="cartStore.totalCount > 0" class="badge-count">
          {{ cartStore.totalCount }}
        </span>
      </router-link>
    </div>
  </header>
</template>

<style scoped>
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 2rem;
  background: #ffffff;
  border-bottom: 1px solid var(--border-color);
  position: sticky;
  top: 0;
  z-index: 40;
}

.topbar-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--dark);
  letter-spacing: -0.01em;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.address-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 0.85rem;
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-full);
  cursor: pointer;
  transition: var(--transition);
  max-width: 280px;
}

.address-btn:hover {
  border-color: var(--primary);
  background: var(--primary-light);
}

.pin {
  font-size: 1rem;
  color: var(--primary);
}

.addr-meta {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.addr-meta small {
  font-size: 0.65rem;
  color: var(--text-muted);
  text-transform: uppercase;
  font-weight: 700;
  line-height: 1;
}

.addr-meta strong {
  font-size: 0.8rem;
  color: var(--dark);
  font-weight: 600;
}

.chevron {
  font-size: 0.6rem;
  color: var(--text-light);
}

.cart-shortcut {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.85rem;
  background: var(--dark);
  color: #ffffff;
  border-radius: var(--radius-md);
  font-size: 0.825rem;
  font-weight: 700;
  text-decoration: none;
  transition: var(--transition);
}

.cart-shortcut:hover {
  background: var(--dark-surface);
}

.badge-count {
  background: var(--accent);
  color: #ffffff;
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  border-radius: var(--radius-full);
  font-weight: 800;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 768px) {
  .topbar {
    padding: 0 1rem;
  }
  .address-btn {
    display: none;
  }
}
</style>
