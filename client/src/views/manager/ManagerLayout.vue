<script setup>
import { ref, onMounted, onUnmounted, provide } from 'vue';
import ManagerSidebar from './ManagerSidebar.vue';
import { useAuthStore } from '../../stores/auth';

const authStore = useAuthStore();
const isStoreOpen = ref(true);

// Minimized sidebar state with persistent memory
const isSidebarMinimized = ref(localStorage.getItem('freshmart_sidebar_minimized') === 'true');
const isContentWide = ref(localStorage.getItem('freshmart_content_wide') === 'true');

function toggleSidebar() {
  isSidebarMinimized.value = !isSidebarMinimized.value;
  localStorage.setItem('freshmart_sidebar_minimized', isSidebarMinimized.value ? 'true' : 'false');
}

function toggleContentWide() {
  isContentWide.value = !isContentWide.value;
  localStorage.setItem('freshmart_content_wide', isContentWide.value ? 'true' : 'false');
}

function toggleStoreOpen() {
  isStoreOpen.value = !isStoreOpen.value;
}

// Global keyboard shortcut: Ctrl+B or Cmd+B to toggle sidebar minimization
function handleKeydown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
    e.preventDefault();
    toggleSidebar();
  }
}

// Provide sidebar state to all children views (e.g. Inventory, Orders) so they can trigger wide mode during specific access
provide('sidebarState', {
  isMinimized: isSidebarMinimized,
  toggle: toggleSidebar,
  isContentWide: isContentWide,
  toggleContentWide: toggleContentWide,
});

onMounted(async () => {
  window.addEventListener('keydown', handleKeydown);

  // If not logged in as store manager, automatically authenticate Sara Manager for prototype
  if (!authStore.isAuthenticated || authStore.userRole !== 'store_manager') {
    await authStore.login('+251911000002', 'password123');
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <div
    class="manager-layout"
    :class="{
      'sidebar-collapsed': isSidebarMinimized,
      'canvas-wide': isContentWide || isSidebarMinimized
    }"
  >
    <!-- Dedicated Slim / Collapsible Manager Sidebar -->
    <ManagerSidebar
      :is-minimized="isSidebarMinimized"
      @toggle-minimize="toggleSidebar"
    />

    <!-- Manager Main Viewport -->
    <div class="manager-viewport">
      <!-- Minimalist Top Bar with Nav Minimizer Control -->
      <header class="manager-topbar">
        <div class="topbar-left">
          <!-- One-click Nav Bar Minimizer Toggle -->
          <button
            class="nav-toggle-btn"
            @click="toggleSidebar"
            :class="{ active: isSidebarMinimized }"
            :title="isSidebarMinimized ? 'Expand navigation bar' : 'Minimize navigation bar for extra table space'"
            aria-label="Toggle Navigation Bar"
          >
            <span class="toggle-icon">{{ isSidebarMinimized ? '⇥' : '⇤' }}</span>
            <span class="toggle-text">{{ isSidebarMinimized ? 'Expand Nav' : 'Minimize Nav' }}</span>
          </button>

          <span class="topbar-separator"></span>

          <span class="branch-label">Bole Medhanialem Flagship • Store Console</span>

          <!-- Visual indicator when wide space mode is engaged -->
          <span
            v-if="isSidebarMinimized"
            class="space-badge"
            title="Navigation bar is minimized: page has maximum width for data tables and catalog items"
          >
            ⚡ Max Space Mode Active
          </span>
        </div>

        <div class="topbar-right">
          <!-- Full Width Canvas Toggle for Edge-to-Edge Data Access -->
          <button
            class="canvas-mode-btn"
            @click="toggleContentWide"
            :class="{ active: isContentWide }"
            :title="isContentWide ? 'Reset to centered container' : 'Expand content to 100% full screen width'"
          >
            <span class="canvas-icon">⛶</span>
            <span>{{ isContentWide ? 'Centered' : 'Full Width' }}</span>
          </button>

          <!-- Store Online Toggle -->
          <button
            class="store-status-btn"
            :class="{ open: isStoreOpen }"
            @click="toggleStoreOpen"
            title="Toggle store intake"
          >
            <span class="status-indicator"></span>
            <span>{{ isStoreOpen ? 'Accepting Orders' : 'Store Paused' }}</span>
          </button>

          <!-- Shortcuts -->
          <router-link to="/admin123/login" class="nav-shortcut">
            Login
          </router-link>
          <router-link to="/admin123/signup" class="nav-shortcut signup-shortcut">
            + New Manager
          </router-link>
        </div>
      </header>

      <!-- Manager Sub-Page Content with Dynamic Full-Width Expansion -->
      <main class="manager-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.manager-layout {
  display: flex;
  min-height: 100vh;
  width: 100%;
  background: #ffffff;
}

.manager-viewport {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: #ffffff;
  transition: all 0.2s ease;
}

.manager-topbar {
  height: 50px;
  background: #ffffff;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.25rem;
  position: sticky;
  top: 0;
  z-index: 40;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

/* Nav Toggle Button */
.nav-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.6rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 5px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  transition: all 0.15s ease;
}

.nav-toggle-btn:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.nav-toggle-btn.active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}

.toggle-icon {
  font-size: 0.9rem;
  line-height: 1;
}

.topbar-separator {
  width: 1px;
  height: 18px;
  background: #e5e7eb;
}

.branch-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #374151;
}

.space-badge {
  font-size: 0.68rem;
  font-weight: 700;
  color: #15803d;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.canvas-mode-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s ease;
}

.canvas-mode-btn:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.canvas-mode-btn.active {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #0f172a;
  font-weight: 600;
}

.canvas-icon {
  font-size: 0.85rem;
}

.store-status-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.3rem 0.65rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #4b5563;
  cursor: pointer;
  transition: all 0.15s ease;
}

.store-status-btn:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.store-status-btn.open {
  border-color: #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

.status-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.nav-shortcut {
  font-size: 0.75rem;
  font-weight: 500;
  color: #4b5563;
  padding: 0.3rem 0.65rem;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  text-decoration: none;
  transition: all 0.15s ease;
}

.nav-shortcut:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
}

.signup-shortcut {
  background: #111827;
  color: #ffffff;
  border-color: #111827;
}

.signup-shortcut:hover {
  background: #0369a1;
  color: #ffffff;
}

.manager-content {
  flex: 1;
  padding: 1.5rem 1.75rem 3rem;
  max-width: 1200px;
  width: 100%;
  transition: max-width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Maximized space when sidebar is collapsed or full width canvas mode is active */
.manager-layout.sidebar-collapsed .manager-content,
.manager-layout.canvas-wide .manager-content {
  max-width: 100%;
}
</style>
