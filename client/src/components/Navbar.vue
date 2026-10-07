<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';
import { useAddressStore } from '../stores/address';

defineProps({
  branches: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['open-address-modal']);

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const addressStore = useAddressStore();

const activeAddressSummary = computed(() => {
  const addr = addressStore.activeAddress;
  if (!addr) return 'Set delivery address';
  return `${addr.building_name_or_no || addr.street_name || 'Home'}, #${addr.house_villa_no}`;
});

function handleLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <header class="navbar-wrapper glass">
    <div class="app-container navbar-inner">
      <!-- Brand Logo -->
      <router-link to="/" class="brand-link">
        <div class="logo-icon">🛒</div>
        <div>
          <span class="brand-name">Fresh<span class="brand-accent">Supermarket</span></span>
          <span class="brand-tagline">Express Grocery Delivery</span>
        </div>
      </router-link>

      <!-- Active Delivery Address Pill (Granular Address Trigger) -->
      <div class="address-pill" @click="emit('open-address-modal')">
        <span class="pin-icon">📍</span>
        <div class="address-text-group">
          <span class="address-label">Delivering to</span>
          <span class="address-val truncate">{{ activeAddressSummary }}</span>
        </div>
        <span class="chevron">▼</span>
      </div>

      <!-- Action items -->
      <div class="nav-actions">
        <!-- Live Track Shortcut if order exists -->
        <router-link to="/tracking/1" class="nav-btn nav-track-btn">
          <span class="pulse-dot"></span>
          <span>Live Track Demo</span>
        </router-link>

        <!-- Cart Button -->
        <router-link to="/cart" class="nav-btn cart-btn">
          <span class="cart-icon">🛍️</span>
          <span>Cart</span>
          <span v-if="cartStore.totalCount > 0" class="cart-badge">
            {{ cartStore.totalCount }}
          </span>
        </router-link>

        <!-- User Authentication status -->
        <div v-if="authStore.isAuthenticated" class="user-menu">
          <div class="user-avatar-badge">
            <span class="role-pill">{{ authStore.userRole }}</span>
            <span class="user-name">{{ authStore.userName.split(' ')[0] }}</span>
          </div>
          <button class="logout-btn" @click="handleLogout" title="Logout">🚪</button>
        </div>
        <div v-else>
          <router-link to="/login" class="btn btn-primary btn-sm">
            Sign In
          </router-link>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.navbar-wrapper {
  position: sticky;
  top: 0;
  z-index: 100;
  border-bottom: 1px solid var(--border-color);
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.navbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 72px;
  gap: 1rem;
}

.brand-link {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.logo-icon {
  font-size: 1.85rem;
  background: var(--primary-light);
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-md);
}

.brand-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--dark);
  letter-spacing: -0.02em;
}

.brand-accent {
  color: var(--primary);
}

.brand-tagline {
  display: block;
  font-size: 0.7rem;
  color: var(--text-muted);
  font-weight: 500;
  margin-top: -3px;
}

.address-pill {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: #ffffff;
  border: 1px solid var(--border-color);
  padding: 0.45rem 0.9rem;
  border-radius: var(--radius-full);
  cursor: pointer;
  transition: var(--transition);
  max-width: 320px;
}

.address-pill:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-sm);
}

.pin-icon {
  font-size: 1.15rem;
  color: var(--primary);
}

.address-text-group {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.address-label {
  font-size: 0.65rem;
  color: var(--text-muted);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.address-val {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--dark);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chevron {
  font-size: 0.65rem;
  color: var(--text-light);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.nav-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 0.95rem;
  border-radius: var(--radius-md);
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid var(--border-color);
  background: #ffffff;
  transition: var(--transition);
}

.nav-track-btn {
  background: var(--primary-light);
  color: var(--primary);
  border-color: #a7f3d0;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse 1.6s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.9); opacity: 0.8; }
  50% { transform: scale(1.4); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.8; }
}

.cart-btn {
  position: relative;
  background: var(--dark);
  color: #ffffff;
  border-color: var(--dark);
}
.cart-btn:hover {
  background: var(--dark-surface);
}

.cart-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background: var(--accent);
  color: #ffffff;
  border-radius: var(--radius-full);
  font-size: 0.72rem;
  padding: 0.15rem 0.45rem;
  font-weight: 800;
  border: 2px solid #ffffff;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-avatar-badge {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #f1f5f9;
  padding: 0.35rem 0.75rem;
  border-radius: var(--radius-full);
}

.role-pill {
  font-size: 0.65rem;
  background: var(--primary);
  color: #ffffff;
  padding: 0.15rem 0.4rem;
  border-radius: var(--radius-full);
  text-transform: uppercase;
  font-weight: 700;
}

.user-name {
  font-size: 0.825rem;
  font-weight: 600;
}

.logout-btn {
  font-size: 1rem;
  padding: 0.4rem;
  border-radius: var(--radius-sm);
  transition: var(--transition);
}
.logout-btn:hover {
  background: #fee2e2;
}

@media (max-width: 768px) {
  .address-pill {
    display: none;
  }
}
</style>
