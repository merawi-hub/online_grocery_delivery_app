<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCartStore } from '../stores/cart';

const router = useRouter();
const authStore = useAuthStore();
const cartStore = useCartStore();

const showAccountMenu = ref(false);
const searchQuery = ref('');

const cartItemCount = computed(() => cartStore.totalCount);
const isAuthenticated = computed(() => authStore.isAuthenticated);
const userName = computed(() => authStore.userName);

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.push(`/?search=${encodeURIComponent(searchQuery.value)}`);
  }
}

function handleLogout() {
  authStore.logout();
  showAccountMenu.value = false;
  router.push('/');
}

function goToCart() {
  router.push('/cart');
}

function goToAccount() {
  router.push('/account');
  showAccountMenu.value = false;
}

function goToOrders() {
  router.push('/account/orders');
  showAccountMenu.value = false;
}
</script>

<template>
  <div class="public-layout">
    <!-- Top Utility Bar -->
    <div class="utility-bar">
      <div class="container utility-content">
        <div class="utility-links">
          <a href="#" class="utility-link">Help Center</a>
          <router-link to="/tracking" class="utility-link">Track Order</router-link>
        </div>
        <div class="utility-actions">
          <span class="utility-text">📍 Addis Ababa</span>
        </div>
      </div>
    </div>

    <!-- Main Header -->
    <header class="main-header">
      <div class="container header-content">
        <!-- Logo -->
        <router-link to="/" class="logo">
          <span class="logo-icon">🛒</span>
          <div class="logo-text">
            <span class="logo-title">FreshMart</span>
            <span class="logo-subtitle">Grocery</span>
          </div>
        </router-link>

        <!-- Search Bar -->
        <div class="search-section">
          <form @submit.prevent="handleSearch" class="search-form">
            <span class="search-icon">🔍</span>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search for products, brands, and more..."
              class="search-input"
            />
            <button v-if="searchQuery" type="button" class="clear-btn" @click="searchQuery = ''">×</button>
          </form>
        </div>

        <!-- Actions -->
        <div class="header-actions">
          <!-- Cart -->
          <button class="action-btn cart-btn" @click="goToCart">
            <span class="cart-icon">🛒</span>
            <span class="action-label">Cart</span>
            <span v-if="cartItemCount > 0" class="cart-badge">{{ cartItemCount }}</span>
          </button>

          <!-- Account -->
          <div v-if="isAuthenticated" class="account-dropdown">
            <button class="action-btn account-btn" @click="showAccountMenu = !showAccountMenu">
              <span class="account-icon">👤</span>
              <span class="action-label">{{ userName }}</span>
              <span class="dropdown-arrow">▼</span>
            </button>

            <div v-if="showAccountMenu" class="dropdown-menu">
              <button @click="goToAccount" class="dropdown-item">
                <span class="item-icon">👤</span>
                My Account
              </button>
              <button @click="goToOrders" class="dropdown-item">
                <span class="item-icon">📦</span>
                My Orders
              </button>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout">
                <span class="item-icon">🚪</span>
                Sign Out
              </button>
            </div>
          </div>

          <router-link v-else to="/login" class="btn-signin">
            Sign In
          </router-link>
        </div>
      </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="nav-bar">
      <div class="container nav-content">
        <router-link to="/" class="nav-link" :class="{ active: $route.path === '/' }">
          <span class="nav-link-icon">🏠</span> Home
        </router-link>
        <router-link to="/products" class="nav-link" :class="{ active: $route.path === '/products' }">
          <span class="nav-link-icon">🥦</span> All Products
        </router-link>
        <router-link to="/tracking" class="nav-link" :class="{ active: $route.path.startsWith('/tracking') }">
          <span class="nav-link-icon">🚚</span> Track Order
        </router-link>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="main-footer">
      <div class="container footer-content">
        <div class="footer-section">
          <h4 class="footer-title">About FreshMart</h4>
          <p class="footer-text">
            Your trusted online grocery store delivering fresh products to your doorstep.
          </p>
        </div>
        <div class="footer-section">
          <h4 class="footer-title">Customer Service</h4>
          <a href="#" class="footer-link">Help Center</a>
          <a href="#" class="footer-link">Track Order</a>
          <a href="#" class="footer-link">Returns</a>
        </div>
        <div class="footer-section">
          <h4 class="footer-title">Contact</h4>
          <p class="footer-text">📞 +251 11 123 4567</p>
          <p class="footer-text">📧 support@freshmart.et</p>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="container">
          <p class="copyright">© 2026 FreshMart. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.public-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fafaf9;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
  width: 100%;
}

/* Utility Bar */
.utility-bar {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.5rem 0;
}

.utility-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.utility-links, .utility-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.utility-link {
  font-size: 0.8125rem;
  color: #64748b;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.utility-link:hover {
  color: #0f172a;
}

.utility-text {
  font-size: 0.8125rem;
  color: #64748b;
  font-weight: 500;
}

/* Main Header */
.main-header {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.nav-bar {
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

.nav-content {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 0.5rem 1rem;
}

.nav-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.9rem;
  font-weight: 600;
  color: #475569;
  text-decoration: none;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  transition: all 0.15s ease;
}

.nav-link:hover {
  color: #0f172a;
  background: #f8fafc;
}

.nav-link.active {
  color: #0f172a;
  background: #f1f5f9;
  font-weight: 700;
}

.nav-link-icon {
  font-size: 1.1rem;
}

.header-content {
  display: flex;
  align-items: center;
  gap: 2rem;
}

/* Logo */
.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  flex-shrink: 0;
}

.logo-icon {
  font-size: 2rem;
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.logo-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.logo-subtitle {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Search */
.search-section {
  flex: 1;
  max-width: 600px;
}

.search-form {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.625rem 1rem;
  gap: 0.75rem;
  transition: all 0.2s;
}

.search-form:focus-within {
  border-color: #94a3b8;
  background: #ffffff;
}

.search-icon {
  font-size: 1.125rem;
  color: #64748b;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0;
  font-size: 0.875rem;
  background: transparent;
  color: #0f172a;
  min-width: 0;
}

.search-input::placeholder {
  color: #94a3b8;
}

.clear-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  flex-shrink: 0;
  transition: color 0.2s;
}

.clear-btn:hover {
  color: #64748b;
}

/* Header Actions */
.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0f172a;
  position: relative;
}

.action-btn:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.cart-icon, .account-icon {
  font-size: 1.25rem;
}

.action-label {
  font-weight: 600;
}

.dropdown-arrow {
  font-size: 0.625rem;
  color: #64748b;
}

.cart-badge {
  position: absolute;
  top: -0.25rem;
  right: -0.25rem;
  background: #ef4444;
  color: #ffffff;
  font-size: 0.6875rem;
  font-weight: 700;
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
  min-width: 1.25rem;
  text-align: center;
}

.btn-signin {
  padding: 0.625rem 1.25rem;
  background: #0f172a;
  color: #ffffff;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-signin:hover {
  background: #1e293b;
}

/* Account Dropdown */
.account-dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  min-width: 200px;
  padding: 0.5rem;
  z-index: 50;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.75rem 1rem;
  background: transparent;
  border: none;
  border-radius: 6px;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0f172a;
  cursor: pointer;
  transition: background 0.2s;
}

.dropdown-item:hover {
  background: #f8fafc;
}

.dropdown-item.logout {
  color: #dc2626;
}

.dropdown-item.logout:hover {
  background: #fef2f2;
}

.item-icon {
  font-size: 1rem;
}

.dropdown-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 0.5rem 0;
}

/* Main Content */
.main-content {
  flex: 1;
}

/* Footer */
.main-footer {
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  margin-top: 4rem;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  padding: 3rem 1.5rem;
}

.footer-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0f172a;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}

.footer-text {
  font-size: 0.875rem;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
}

.footer-link {
  font-size: 0.875rem;
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
}

.footer-link:hover {
  color: #0f172a;
}

.footer-bottom {
  background: #f8fafc;
  padding: 1.5rem 0;
  border-top: 1px solid #e2e8f0;
}

.copyright {
  text-align: center;
  font-size: 0.8125rem;
  color: #94a3b8;
  margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
  .header-content {
    gap: 1rem;
  }

  .search-section {
    max-width: 400px;
  }

  .action-label {
    display: none;
  }

  .logo-text {
    display: none;
  }
}

@media (max-width: 768px) {
  .utility-bar {
    display: none;
  }

  .header-content {
    flex-wrap: wrap;
  }

  .search-section {
    order: 3;
    flex-basis: 100%;
    max-width: 100%;
    margin-top: 0.75rem;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}
</style>
