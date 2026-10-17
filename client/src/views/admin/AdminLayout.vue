<script setup>
import { ref, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const activeTab = ref('products');

// Watch route changes to update active tab
watch(
  () => route.path,
  (newPath) => {
    if (newPath.includes('/products')) activeTab.value = 'products';
    else if (newPath.includes('/categories')) activeTab.value = 'categories';
    else if (newPath.includes('/operations')) activeTab.value = 'operations';
  },
  { immediate: true }
);

function handleLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <div class="admin-wrapper">
    <!-- Top Bar Navigation -->
    <header class="admin-header">
      <div class="header-left">
        <div class="brand-group">
          <span class="brand-badge">Admin Dashboard</span>
          <h1 class="brand-title">Product & Category Management</h1>
        </div>
        <p class="brand-desc">Manage global product catalog and categories for all stores</p>
      </div>

      <div class="header-right">
        <div class="admin-profile-pill">
          <div class="admin-avatar">{{ authStore.user?.name?.charAt(0)?.toUpperCase() || 'A' }}</div>
          <div class="admin-meta">
            <strong>{{ authStore.user?.name || 'Admin' }}</strong>
            <small>Administrator</small>
          </div>
        </div>

        <button class="btn btn-danger-outline" @click="handleLogout">
          Sign Out
        </button>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="tabs-bar">
      <button
        class="tab-link"
        :class="{ active: activeTab === 'products' }"
        @click="activeTab = 'products'; router.push('/admin/products')"
      >
        📦 Products
      </button>

      <button
        class="tab-link"
        :class="{ active: activeTab === 'categories' }"
        @click="activeTab = 'categories'; router.push('/admin/categories')"
      >
        🏷️ Categories
      </button>

      <button
        class="tab-link"
        :class="{ active: activeTab === 'operations' }"
        @click="activeTab = 'operations'; router.push('/admin/operations')"
      >
        👑 Operations Manager
      </button>
    </div>

    <!-- Router View for nested routes -->
    <router-view />
  </div>
</template>

<style scoped>
.admin-wrapper {
  min-height: 100vh;
  background: #f8fafc;
}

.admin-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  flex: 1;
}

.brand-group {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.brand-badge {
  background: #0f172a;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
}

.brand-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}

.brand-desc {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.admin-profile-pill {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border-radius: 8px;
}

.admin-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #0f172a;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.admin-meta {
  display: flex;
  flex-direction: column;
}

.admin-meta strong {
  font-size: 0.875rem;
  color: #0f172a;
}

.admin-meta small {
  font-size: 0.75rem;
  color: #64748b;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-danger-outline {
  background: white;
  border: 1px solid #dc2626;
  color: #dc2626;
}

.btn-danger-outline:hover {
  background: #dc2626;
  color: white;
}

.tabs-bar {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  padding: 0 2rem;
}

.tab-link {
  padding: 1rem 1.5rem;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-link:hover {
  color: #0f172a;
  background: #f8fafc;
}

.tab-link.active {
  color: #0f172a;
  border-bottom-color: #0f172a;
}
</style>
