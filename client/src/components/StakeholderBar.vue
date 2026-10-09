<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const roles = [
  {
    key: 'customer',
    label: 'Customer Storefront',
    icon: '🛒',
    user: 'User1',
    phone: '0969644384',
    pass: 'password123',
    route: '/customer',
    desc: 'Browse, Cart, Order, Live Tracking & Confirm Delivery',
  },
  {
    key: 'store_manager',
    label: 'Supermarket Stock Manager',
    icon: '🏬',
    user: 'Sara Manager',
    phone: '0911000002',
    pass: 'password123',
    route: '/admin123/inventory',
    desc: 'Add/Edit/Delete Products, Stock Count & Pack Orders',
  },
  {
    key: 'admin',
    label: 'General Operations Manager',
    icon: '👑',
    user: 'General Manager (admin)',
    login: 'admin',
    pass: 'admin123',
    route: '/general-manager',
    desc: 'Switch Stock Managers, Assign Deliverers & Hire Staff',
  },
  {
    key: 'driver',
    label: 'Deliverer / Courier Fleet',
    icon: '🛵',
    user: 'Yonas Driver',
    phone: '0911000004',
    pass: 'password123',
    route: '/driver',
    desc: 'Pick Up Orders, Live Delivery Map & Confirm Handover',
  },
];

const currentRoleKey = computed(() => {
  if (route.path.startsWith('/admin123')) return 'store_manager';
  if (route.path.startsWith('/general-manager') || route.path.startsWith('/admin')) return 'admin';
  if (route.path.startsWith('/driver')) return 'driver';
  if (route.path.startsWith('/customer') || route.path === '/cart' || route.path.startsWith('/tracking')) return 'customer';
  return authStore.userRole || 'customer';
});

async function switchToRole(roleItem) {
  const loginIdent = roleItem.login || roleItem.phone;
  await authStore.login(loginIdent, roleItem.pass);
  router.push(roleItem.route);
}
</script>

<template>
  <div class="stakeholder-bar">
    <div class="bar-inner">
      <div class="ecosystem-tag">
        <span class="pulse-indicator"></span>
        <span class="tag-title">4-Stakeholder Supermarket OS</span>
      </div>

      <div class="roles-switcher">
        <button
          v-for="r in roles"
          :key="r.key"
          type="button"
          class="role-switch-btn"
          :class="{ active: currentRoleKey === r.key }"
          @click="switchToRole(r)"
          :title="r.desc"
        >
          <span class="role-icon">{{ r.icon }}</span>
          <span class="role-name">{{ r.label }}</span>
          <span v-if="currentRoleKey === r.key" class="active-badge">Active</span>
        </button>
      </div>

      <div class="current-session-info">
        <span class="avatar-mini">{{ authStore.userName.charAt(0).toUpperCase() }}</span>
        <div class="user-meta">
          <span class="user-display">{{ authStore.userName }}</span>
          <span class="user-role-label">{{ currentRoleKey.replace('_', ' ').toUpperCase() }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stakeholder-bar {
  background: #0f172a;
  color: #ffffff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.45rem 1.25rem;
  font-family: inherit;
  font-size: 0.82rem;
  position: sticky;
  top: 0;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.bar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  max-width: 1440px;
  margin: 0 auto;
}

.ecosystem-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 700;
  color: #38bdf8;
  letter-spacing: -0.01em;
  white-space: nowrap;
}

.pulse-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: pulse 1.8s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.roles-switcher {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  overflow-x: auto;
}

.role-switch-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.05);
  color: #cbd5e1;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.18s ease;
}

.role-switch-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.28);
}

.role-switch-btn.active {
  background: #10b981;
  color: #ffffff;
  border-color: #10b981;
  box-shadow: 0 2px 10px rgba(16, 185, 129, 0.35);
}

.active-badge {
  font-size: 0.65rem;
  background: rgba(0, 0, 0, 0.25);
  padding: 0.1rem 0.4rem;
  border-radius: 9999px;
  font-weight: 800;
  text-transform: uppercase;
}

.current-session-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.avatar-mini {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #3b82f6;
  color: #ffffff;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.user-meta {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.user-display {
  font-weight: 600;
  color: #f1f5f9;
}

.user-role-label {
  font-size: 0.65rem;
  color: #94a3b8;
  font-weight: 700;
}

@media (max-width: 900px) {
  .ecosystem-tag {
    display: none;
  }
}
</style>
