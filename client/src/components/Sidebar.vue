<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';
import { useAddressStore } from '../stores/address';

const emit = defineEmits(['open-address-modal']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();
const addressStore = useAddressStore();

const activeAddressSummary = computed(() => {
  const addr = addressStore.activeAddress;
  if (!addr) return 'Set address';
  return `${addr.building_name_or_no || addr.street_name || 'Home'}, #${addr.house_villa_no}`;
});

function handleLogout() {
  authStore.logout();
  router.push('/login');
}

// Quick switch between stakeholder portals for demonstration & testing
function switchStakeholderRole(role, targetRoute) {
  const accounts = {
    customer: { phone: '+251911000005', pass: 'password123' },
    picker: { phone: '+251911000003', pass: 'password123' },
    driver: { phone: '+251911000004', pass: 'password123' },
  };

  const cred = accounts[role];
  if (cred) {
    authStore.login(cred.phone, cred.pass).then(() => {
      router.push(targetRoute);
    });
  }
}
</script>

<template>
  <aside class="sidebar">
    <!-- 1. Brand & Branch Header -->
    <div class="sidebar-header">
      <div class="brand">
        <div class="brand-badge">🛒</div>
        <div>
          <span class="brand-title">Fresh<span class="accent-text">Supermarket</span></span>
          <span class="brand-sub">Grocery Delivery OS</span>
        </div>
      </div>

      <div class="branch-pill">
        <span class="status-indicator"></span>
        <div class="branch-info">
          <strong class="branch-name">Bole Flagship Branch</strong>
          <span class="branch-hours">Open 07:30 – 22:30</span>
        </div>
      </div>
    </div>

    <!-- 2. Stakeholder Role Switcher Bar -->
    <div class="role-switcher-container">
      <span class="section-label">Active Stakeholder Portal</span>
      <div class="role-tabs">
        <button
          class="role-tab"
          :class="{ active: authStore.userRole === 'customer' }"
          @click="switchStakeholderRole('customer', '/')"
          title="Customer Shopper View"
        >
          <span>🛒</span> Customer
        </button>
        <button
          class="role-tab"
          :class="{ active: authStore.userRole === 'picker' }"
          @click="switchStakeholderRole('picker', '/picker')"
          title="Supermarket Order Picker"
        >
          <span>📦</span> Picker
        </button>
        <button
          class="role-tab"
          :class="{ active: authStore.userRole === 'driver' }"
          @click="switchStakeholderRole('driver', '/driver')"
          title="Delivery Courier View"
        >
          <span>🛵</span> Driver
        </button>
      </div>
    </div>

    <!-- 3. Navigation Sections (Organized by Portal) -->
    <nav class="sidebar-nav">
      <!-- CUSTOMER NAVIGATION -->
      <template v-if="authStore.userRole === 'customer'">
        <div class="nav-group">
          <span class="nav-group-title">Shopper Experience</span>
          <router-link to="/" class="nav-item" :class="{ active: route.path === '/' }">
            <span class="nav-icon">🧺</span>
            <span>Aisle Storefront</span>
          </router-link>

          <router-link to="/cart" class="nav-item" :class="{ active: route.path === '/cart' }">
            <span class="nav-icon">🛍️</span>
            <span>Basket & Checkout</span>
            <span v-if="cartStore.totalCount > 0" class="nav-badge">
              {{ cartStore.totalCount }}
            </span>
          </router-link>

          <router-link to="/tracking/1" class="nav-item" :class="{ active: route.path.startsWith('/tracking') }">
            <span class="nav-icon">📍</span>
            <span>Live GPS Radar</span>
            <span class="pulse-pill">LIVE</span>
          </router-link>

          <button class="nav-item address-nav-btn" @click="emit('open-address-modal')">
            <span class="nav-icon">🏠</span>
            <div class="address-nav-text">
              <span>Delivery Address</span>
              <small class="truncate">{{ activeAddressSummary }}</small>
            </div>
            <span class="edit-pin">✏️</span>
          </button>
        </div>
      </template>

      <!-- PICKER NAVIGATION -->
      <template v-else-if="authStore.userRole === 'picker'">
        <div class="nav-group">
          <span class="nav-group-title">Store Operations</span>
          <router-link to="/picker" class="nav-item" :class="{ active: route.path === '/picker' }">
            <span class="nav-icon">📋</span>
            <span>Pick-list Queue</span>
            <span class="nav-badge-amber">1 Active</span>
          </router-link>
          <router-link to="/" class="nav-item">
            <span class="nav-icon">📦</span>
            <span>Shelf Inventory Check</span>
          </router-link>
        </div>
      </template>

      <!-- DRIVER NAVIGATION -->
      <template v-else-if="authStore.userRole === 'driver'">
        <div class="nav-group">
          <span class="nav-group-title">Courier Dispatch</span>
          <router-link to="/driver" class="nav-item" :class="{ active: route.path === '/driver' }">
            <span class="nav-icon">🛵</span>
            <span>Active Deliveries</span>
            <span class="nav-badge-green">1 In Transit</span>
          </router-link>
          <router-link to="/tracking/1" class="nav-item">
            <span class="nav-icon">🗺️</span>
            <span>Customer GPS Radar</span>
          </router-link>
        </div>
      </template>

      <!-- ADMIN / MANAGER FALLBACK -->
      <template v-else>
        <div class="nav-group">
          <span class="nav-group-title">General Portal</span>
          <router-link to="/" class="nav-item">
            <span class="nav-icon">🏪</span>
            <span>Store Overview</span>
          </router-link>
          <router-link to="/picker" class="nav-item">
            <span class="nav-icon">📦</span>
            <span>Picker Dashboard</span>
          </router-link>
          <router-link to="/driver" class="nav-item">
            <span class="nav-icon">🛵</span>
            <span>Driver Dispatch</span>
          </router-link>
        </div>
      </template>
    </nav>

    <!-- 4. User Profile & Footer Action -->
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar">
          {{ authStore.userRole === 'driver' ? '🛵' : (authStore.userRole === 'picker' ? '📦' : '👤') }}
        </div>
        <div class="user-details">
          <div class="user-name-row">
            <span class="user-name">{{ authStore.userName }}</span>
            <span class="role-tag">{{ authStore.userRole }}</span>
          </div>
          <span class="user-phone">{{ authStore.userPhone || '+251 91 100 0005' }}</span>
        </div>
        <button class="logout-btn" @click="handleLogout" title="Sign Out">
          🚪
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 280px;
  min-width: 280px;
  background: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  z-index: 50;
}

.sidebar-header {
  padding: 1.5rem 1.25rem 1rem;
  border-bottom: 1px solid var(--sidebar-border);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.brand-badge {
  width: 40px;
  height: 40px;
  background: var(--primary);
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.brand-title {
  display: block;
  font-size: 1.15rem;
  font-weight: 800;
  color: #ffffff;
  letter-spacing: -0.02em;
}

.accent-text {
  color: #34d399;
}

.brand-sub {
  font-size: 0.7rem;
  color: var(--sidebar-text);
  font-weight: 500;
}

.branch-pill {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  background: var(--sidebar-surface);
  border: 1px solid var(--sidebar-border);
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-md);
}

.status-indicator {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 8px #10b981;
}

.branch-info {
  display: flex;
  flex-direction: column;
}

.branch-name {
  font-size: 0.78rem;
  color: #ffffff;
  font-weight: 700;
}

.branch-hours {
  font-size: 0.68rem;
  color: var(--sidebar-text);
}

/* Role Switcher Container */
.role-switcher-container {
  padding: 1rem 1.25rem 0.5rem;
}

.section-label {
  display: block;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--sidebar-text);
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.role-tabs {
  display: flex;
  gap: 0.35rem;
  background: var(--sidebar-surface);
  padding: 0.25rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--sidebar-border);
}

.role-tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--sidebar-text);
  padding: 0.4rem 0.2rem;
  border-radius: var(--radius-sm);
  transition: var(--transition);
}

.role-tab:hover {
  color: #ffffff;
}

.role-tab.active {
  background: var(--primary);
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
}

/* Navigation items */
.sidebar-nav {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem 1rem;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.nav-group-title {
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
  letter-spacing: 0.05em;
  padding: 0.5rem 0.5rem 0.25rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.85rem;
  border-radius: var(--radius-md);
  color: var(--sidebar-text);
  font-size: 0.875rem;
  font-weight: 600;
  transition: var(--transition);
  text-decoration: none;
}

.nav-item:hover {
  background: var(--sidebar-surface);
  color: #ffffff;
}

.nav-item.active {
  background: rgba(5, 150, 105, 0.18);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.nav-icon {
  font-size: 1.15rem;
}

.nav-badge {
  margin-left: auto;
  background: var(--accent);
  color: #ffffff;
  font-size: 0.7rem;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-full);
  font-weight: 800;
}

.nav-badge-amber {
  margin-left: auto;
  background: #f59e0b;
  color: #ffffff;
  font-size: 0.68rem;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-full);
  font-weight: 700;
}

.nav-badge-green {
  margin-left: auto;
  background: #10b981;
  color: #ffffff;
  font-size: 0.68rem;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-full);
  font-weight: 700;
}

.pulse-pill {
  margin-left: auto;
  font-size: 0.65rem;
  font-weight: 800;
  background: rgba(16, 185, 129, 0.25);
  color: #34d399;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-full);
}

.address-nav-btn {
  width: 100%;
  text-align: left;
  background: transparent;
  cursor: pointer;
}

.address-nav-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.address-nav-text small {
  font-size: 0.68rem;
  color: #64748b;
  font-weight: 500;
}

.edit-pin {
  font-size: 0.75rem;
  opacity: 0.7;
}

/* User Card in Footer */
.sidebar-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--sidebar-border);
  background: rgba(15, 23, 42, 0.6);
}

.user-card {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: var(--sidebar-surface);
  border: 1px solid var(--sidebar-border);
  padding: 0.65rem 0.75rem;
  border-radius: var(--radius-md);
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: rgba(5, 150, 105, 0.2);
  border: 1px solid #059669;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.user-name {
  font-size: 0.825rem;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.role-tag {
  font-size: 0.6rem;
  background: var(--primary);
  color: #ffffff;
  padding: 0.1rem 0.3rem;
  border-radius: var(--radius-sm);
  text-transform: uppercase;
  font-weight: 800;
}

.user-phone {
  font-size: 0.68rem;
  color: var(--sidebar-text);
  font-family: monospace;
}

.logout-btn {
  font-size: 1.1rem;
  padding: 0.35rem;
  border-radius: var(--radius-sm);
  color: var(--sidebar-text);
  transition: var(--transition);
}

.logout-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
