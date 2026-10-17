<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const props = defineProps({
  isMinimized: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle-minimize']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

function handleManagerLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <aside class="manager-sidebar" :class="{ 'is-minimized': isMinimized }">
    <!-- Brand Header: Clean Store Name and Branch -->
    <div class="sidebar-header">
      <div class="brand-row">
        <button
          class="brand-logo-btn"
          @click="isMinimized ? emit('toggle-minimize') : null"
          :title="isMinimized ? 'FreshMart • Click to expand' : 'FreshMart Store'"
          aria-label="FreshMart Store"
        >
          <span class="brand-logo">🛒</span>
        </button>

        <div v-if="!isMinimized" class="brand-info">
          <div class="brand-name-row">
            <span class="brand-name">FreshMart</span>
          </div>
          <div class="brand-branch-row">
            <span class="status-dot pulsing"></span>
            <span class="brand-branch-name">Bole Branch</span>
            <span class="brand-branch-badge">Open</span>
          </div>
        </div>

        <!-- Sidebar Collapse / Expand Button in Header -->
        <button
          class="sidebar-toggle-chevron"
          @click="emit('toggle-minimize')"
          :title="isMinimized ? 'Expand sidebar' : 'Minimize sidebar'"
          aria-label="Toggle sidebar width"
        >
          <span class="chevron-icon">{{ isMinimized ? '❯' : '❮' }}</span>
        </button>
      </div>

      <!-- Minimized Mode Branch Dot Indicator -->
      <div
        v-if="isMinimized"
        class="branch-dot-mini"
        title="Bole Branch • Store Open"
      >
        <span class="status-dot pulsing"></span>
      </div>
    </div>

    <!-- Organized Navigation: All items kept together cleanly without scrolling -->
    <nav class="sidebar-nav">
      <div v-if="!isMinimized" class="nav-section-label">OPERATIONS</div>
      <div v-else class="nav-section-divider"></div>

      <router-link
        to="/admin123/dashboard"
        class="nav-link"
        :class="{ active: route.path === '/admin123/dashboard' || route.path === '/admin123' }"
      >
        <span class="nav-icon">📊</span>
        <span v-if="!isMinimized" class="nav-label">Dashboard</span>
        <span v-if="isMinimized" class="nav-tooltip">Dashboard</span>
      </router-link>

      <router-link
        to="/admin123/inventory"
        class="nav-link"
        :class="{ active: route.path === '/admin123/inventory' }"
      >
        <span class="nav-icon">📦</span>
        <span v-if="!isMinimized" class="nav-label">Products</span>
        <span v-if="isMinimized" class="nav-tooltip">Products & Inventory</span>
      </router-link>

      <router-link
        to="/admin123/orders?tab=available"
        class="nav-link"
        :class="{ active: route.path === '/admin123/orders' && (route.query.tab === 'available' || !route.query.tab) }"
      >
        <span class="nav-icon">📥</span>
        <span v-if="!isMinimized" class="nav-label">Available Orders</span>
        <span v-if="isMinimized" class="nav-tooltip">Available Orders</span>
      </router-link>

      <router-link
        to="/admin123/orders?tab=delivered"
        class="nav-link"
        :class="{ active: route.path === '/admin123/orders' && route.query.tab === 'delivered' }"
      >
        <span class="nav-icon">✅</span>
        <span v-if="!isMinimized" class="nav-label">Delivered Orders</span>
        <span v-if="isMinimized" class="nav-tooltip">Delivered Orders</span>
      </router-link>

      <router-link
        to="/admin123/branch"
        class="nav-link"
        :class="{ active: route.path === '/admin123/branch' }"
      >
        <span class="nav-icon">⚙️</span>
        <span v-if="!isMinimized" class="nav-label">Store Settings</span>
        <span v-if="isMinimized" class="nav-tooltip">Store Settings</span>
      </router-link>
    </nav>

    <!-- Manager Profile Footer -->
    <div class="sidebar-footer">
      <div v-if="!isMinimized" class="profile-box">
        <div class="profile-meta">
          <span class="profile-name">{{ authStore.userName || 'Store Manager' }}</span>
          <span class="profile-role">{{ authStore.userRole?.replace('_', ' ') || 'Store Manager' }}</span>
        </div>
        <div class="footer-actions">
          <button class="footer-btn logout-text" @click="handleManagerLogout" title="Sign Out">Log Out</button>
        </div>
      </div>

      <div v-else class="profile-box-mini">
        <button
          class="mini-avatar-btn"
          @click="handleManagerLogout"
          :title="`${authStore.userName || 'Store Manager'} • Click to log out`"
        >
          <span class="avatar-initials">{{ (authStore.userName || 'SM').charAt(0).toUpperCase() }}</span>
          <span class="mini-status-dot"></span>
        </button>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.manager-sidebar {
  width: 242px;
  min-width: 242px;
  height: 100vh;
  background: #ffffff;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  z-index: 50;
  user-select: none;
  overflow: hidden;
  transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1), min-width 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.manager-sidebar.is-minimized {
  width: 64px;
  min-width: 64px;
}

/* Header: Clean Store Name & Branch */
.sidebar-header {
  padding: 0.85rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  flex-shrink: 0;
  transition: padding 0.2s ease;
}

.manager-sidebar.is-minimized .sidebar-header {
  padding: 0.85rem 0.4rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.brand-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  position: relative;
  width: 100%;
}

.manager-sidebar.is-minimized .brand-row {
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
}

.brand-logo-btn {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  padding: 0;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.15s ease-in-out;
}

.brand-logo-btn:hover {
  background: #e0f2fe;
  border-color: #bae6fd;
}

.brand-logo-btn:active {
  background: #bae6fd;
  border-color: #7dd3fc;
}

.brand-logo {
  font-size: 1.25rem;
  line-height: 1;
}

.brand-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
  gap: 0.2rem;
}

.brand-name-row {
  display: flex;
  align-items: center;
}

.brand-name {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
  line-height: 1.2;
}

.brand-branch-row {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-wrap: nowrap;
}

.brand-branch-name {
  font-size: 0.76rem;
  font-weight: 600;
  color: #475569;
  white-space: nowrap;
}

.brand-branch-badge {
  font-size: 0.62rem;
  font-weight: 700;
  color: #15803d;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 9999px;
  padding: 0.05rem 0.35rem;
  line-height: 1.2;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.sidebar-toggle-chevron {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  font-size: 0.72rem;
  transition: all 0.15s ease-in-out;
  margin-left: auto;
  flex-shrink: 0;
}

.sidebar-toggle-chevron:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.sidebar-toggle-chevron:active {
  background: #bae6fd;
  color: #0c4a6e;
}

.manager-sidebar.is-minimized .sidebar-toggle-chevron {
  margin: 0;
  width: 24px;
  height: 24px;
  font-size: 0.7rem;
}

.chevron-icon {
  line-height: 1;
}

.branch-dot-mini {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 0.4rem;
  padding: 0.15rem;
  cursor: help;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #16a34a;
  flex-shrink: 0;
}

.pulsing {
  box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
  animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
  0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
  70% { box-shadow: 0 0 0 5px rgba(22, 163, 74, 0); }
  100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
}

/* Nav: Kept together cleanly without vertical scroll */
.sidebar-nav {
  flex: 1;
  padding: 0.65rem 0.65rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  overflow-y: hidden;
  overflow-x: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.sidebar-nav::-webkit-scrollbar {
  display: none;
}

.manager-sidebar.is-minimized .sidebar-nav {
  padding: 0.65rem 0.35rem;
  align-items: center;
  gap: 0.35rem;
}

.nav-section-label {
  font-size: 0.68rem;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.06em;
  padding: 0.35rem 0.6rem 0.15rem;
  text-transform: uppercase;
}

.nav-section-divider {
  height: 1px;
  width: 24px;
  background: #e2e8f0;
  margin: 0.35rem auto;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.8rem;
  min-height: 44px;
  border-radius: 8px;
  font-size: 0.88rem;
  font-weight: 500;
  color: #334155;
  text-decoration: none;
  transition: all 0.15s ease-in-out;
  border: 1px solid transparent;
  background: transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
  position: relative;
  line-height: 1.25;
}

.manager-sidebar.is-minimized .nav-link {
  width: 44px;
  height: 42px;
  padding: 0;
  justify-content: center;
  border-radius: 8px;
}

/* Default Active Route State */
.nav-link.active {
  background: #f1f5f9;
  color: #0f172a;
  font-weight: 600;
  border-color: #e2e8f0;
}

.manager-sidebar.is-minimized .nav-link.active {
  background: #0f172a;
  color: #ffffff;
}

/* Hover and Click / Active State: Light Blue with High-Visibility Text */
.nav-link:hover {
  background: #e0f2fe !important;
  color: #0369a1 !important;
  border-color: #bae6fd !important;
  font-weight: 600;
}

.nav-link:hover .nav-label {
  color: #0369a1 !important;
}

.nav-link:active {
  background: #bae6fd !important;
  color: #0c4a6e !important;
  border-color: #7dd3fc !important;
  transform: scale(0.99);
}

.nav-link:active .nav-label {
  color: #0c4a6e !important;
}

.manager-sidebar.is-minimized .nav-link:hover {
  background: #e0f2fe !important;
  color: #0369a1 !important;
  border-color: #bae6fd !important;
}

.nav-icon {
  font-size: 1.05rem;
  line-height: 1;
  flex-shrink: 0;
  transition: transform 0.15s ease;
}

.nav-link:hover .nav-icon {
  transform: scale(1.08);
}

.nav-label {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: color 0.15s ease;
}

/* Floating Tooltip in Minimized Mode */
.nav-tooltip {
  display: none;
  position: absolute;
  left: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: #0f172a;
  color: #ffffff;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  font-size: 0.74rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  z-index: 1000;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}

.nav-tooltip::before {
  content: '';
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border-width: 5px;
  border-style: solid;
  border-color: transparent #0f172a transparent transparent;
}

.manager-sidebar.is-minimized .nav-link:hover .nav-tooltip {
  display: block;
}

/* Footer Profile Box */
.sidebar-footer {
  padding: 0.65rem 0.75rem;
  border-top: 1px solid #f1f5f9;
  background: #ffffff;
  flex-shrink: 0;
}

.manager-sidebar.is-minimized .sidebar-footer {
  padding: 0.55rem 0.35rem;
  display: flex;
  justify-content: center;
}

.profile-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
}

.profile-meta {
  display: flex;
  flex-direction: column;
}

.profile-name {
  font-size: 0.84rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.25;
}

.profile-role {
  font-size: 0.68rem;
  color: #64748b;
  text-transform: capitalize;
  line-height: 1.2;
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.35rem;
  padding-top: 0.35rem;
  border-top: 1px solid #e2e8f0;
}

.footer-btn {
  font-size: 0.72rem;
  font-weight: 600;
  color: #475569;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 4px;
  padding: 0.2rem 0.45rem;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}

.footer-btn:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.footer-btn:active {
  background: #bae6fd;
  color: #0c4a6e;
}

.logout-text {
  color: #dc2626;
}

.logout-text:hover {
  background: #e0f2fe;
  color: #b91c1c;
  border-color: #bae6fd;
}

.logout-text:active {
  background: #bae6fd;
  color: #991b1b;
}

.divider {
  font-size: 0.65rem;
  color: #cbd5e1;
}

/* Minimized profile avatar button */
.profile-box-mini {
  display: flex;
  align-items: center;
  justify-content: center;
}

.mini-avatar-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #0f172a;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 700;
  cursor: pointer;
  border: 2px solid #e2e8f0;
  position: relative;
  transition: all 0.15s ease-in-out;
}

.mini-avatar-btn:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #0284c7;
  box-shadow: 0 0 0 3px rgba(224, 242, 254, 0.8);
}

.mini-avatar-btn:active {
  background: #bae6fd;
  color: #0c4a6e;
}

.mini-status-dot {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 7px;
  height: 7px;
  background: #16a34a;
  border: 2px solid #ffffff;
  border-radius: 50%;
}

.truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
