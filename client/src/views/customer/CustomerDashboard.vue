<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';

const router = useRouter();
const authStore = useAuthStore();

const orders = ref([]);
const addresses = ref([]);
const loading = ref(true);

const user = computed(() => authStore.user);

async function loadDashboardData() {
  loading.value = true;
  try {
    // Load recent orders
    const ordersRes = await api.get('/orders');
    orders.value = ordersRes.data.slice(0, 3); // Show latest 3

    // Load addresses
    const addressRes = await api.get('/addresses');
    addresses.value = addressRes.data;
  } catch (err) {
    console.error('Failed to load dashboard data', err);
  } finally {
    loading.value = false;
  }
}

function goToOrders() {
  router.push('/account/orders');
}

function goToAddresses() {
  router.push('/account/addresses');
}

function goToProfile() {
  router.push('/account/profile');
}

function startShopping() {
  router.push('/');
}

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="customer-dashboard">
    <div class="container">
      <!-- Welcome Section -->
      <div class="welcome-section">
        <div class="welcome-content">
          <h1 class="welcome-title">Welcome back, {{ user?.name || 'Customer' }}!</h1>
          <p class="welcome-subtitle">Manage your orders, addresses, and profile</p>
        </div>
        <button @click="startShopping" class="btn-shop">
          🛒 Start Shopping
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading your dashboard...</p>
      </div>

      <!-- Dashboard Grid -->
      <div v-else class="dashboard-grid">
        <!-- Recent Orders -->
        <div class="dashboard-card orders-card">
          <div class="card-header">
            <h2 class="card-title">📦 Recent Orders</h2>
            <button @click="goToOrders" class="view-all-link">View All</button>
          </div>

          <div v-if="orders.length === 0" class="empty-state">
            <span class="empty-icon">📦</span>
            <p class="empty-text">No orders yet</p>
            <button @click="startShopping" class="btn-secondary">Browse Products</button>
          </div>

          <div v-else class="orders-list">
            <div v-for="order in orders" :key="order.id" class="order-item">
              <div class="order-header">
                <span class="order-id">#{{ order.order_number }}</span>
                <span class="order-status" :class="`status-${order.status}`">
                  {{ order.status }}
                </span>
              </div>
              <div class="order-details">
                <span class="order-date">{{ new Date(order.created_at).toLocaleDateString() }}</span>
                <span class="order-total">ETB {{ order.estimated_total }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Addresses -->
        <div class="dashboard-card addresses-card">
          <div class="card-header">
            <h2 class="card-title">📍 Delivery Addresses</h2>
            <button @click="goToAddresses" class="view-all-link">Manage</button>
          </div>

          <div v-if="addresses.length === 0" class="empty-state">
            <span class="empty-icon">📍</span>
            <p class="empty-text">No addresses saved</p>
            <button @click="goToAddresses" class="btn-secondary">Add Address</button>
          </div>

          <div v-else class="addresses-list">
            <div v-for="address in addresses.slice(0, 2)" :key="address.id" class="address-item">
              <div class="address-header">
                <span class="address-label">{{ address.label }}</span>
                <span v-if="address.is_default" class="default-badge">Default</span>
              </div>
              <p class="address-text">
                {{ address.street_name }}, {{ address.district_area }}
              </p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card actions-card">
          <div class="card-header">
            <h2 class="card-title">⚡ Quick Actions</h2>
          </div>

          <div class="actions-grid">
            <button @click="goToProfile" class="action-item">
              <span class="action-icon">👤</span>
              <span class="action-label">Edit Profile</span>
            </button>
            <button @click="goToOrders" class="action-item">
              <span class="action-icon">📦</span>
              <span class="action-label">Order History</span>
            </button>
            <button @click="goToAddresses" class="action-item">
              <span class="action-icon">📍</span>
              <span class="action-label">Addresses</span>
            </button>
            <button @click="startShopping" class="action-item">
              <span class="action-icon">🛒</span>
              <span class="action-label">Shop Now</span>
            </button>
          </div>
        </div>

        <!-- Account Info -->
        <div class="dashboard-card info-card">
          <div class="card-header">
            <h2 class="card-title">👤 Account Information</h2>
          </div>

          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Name</span>
              <span class="info-value">{{ user?.name }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Phone</span>
              <span class="info-value">{{ user?.phone_number }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Email</span>
              <span class="info-value">{{ user?.email || 'Not provided' }}</span>
            </div>
          </div>

          <button @click="goToProfile" class="btn-outline">Update Profile</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.customer-dashboard {
  min-height: 100vh;
  background: #fafaf9;
  padding: 2rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1.5rem;
}

/* Welcome Section */
.welcome-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 12px;
}

.welcome-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.25rem;
}

.welcome-subtitle {
  font-size: 1rem;
  color: #64748b;
}

.btn-shop {
  padding: 0.75rem 1.5rem;
  background: #0f172a;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  white-space: nowrap;
}

.btn-shop:hover {
  background: #1e293b;
}

/* Loading State */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  gap: 1rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #0f172a;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Dashboard Grid */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.dashboard-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.orders-card {
  grid-column: 1 / -1;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.view-all-link {
  font-size: 0.875rem;
  color: #0f172a;
  font-weight: 600;
  background: none;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
}

.view-all-link:hover {
  color: #64748b;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 1rem;
  text-align: center;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
  opacity: 0.5;
}

.empty-text {
  font-size: 0.9375rem;
  color: #64748b;
  margin-bottom: 1rem;
}

.btn-secondary {
  padding: 0.625rem 1.25rem;
  background: #f8fafc;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

/* Orders List */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.order-item {
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
}

.order-item:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.order-id {
  font-size: 0.875rem;
  font-weight: 700;
  color: #0f172a;
}

.order-status {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.625rem;
  border-radius: 9999px;
  text-transform: capitalize;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed, .status-preparing {
  background: #dbeafe;
  color: #1e40af;
}

.status-delivered {
  background: #d1fae5;
  color: #065f46;
}

.order-details {
  display: flex;
  justify-content: space-between;
  font-size: 0.8125rem;
  color: #64748b;
}

.order-total {
  font-weight: 700;
  color: #0f172a;
}

/* Addresses List */
.addresses-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.address-item {
  padding: 0.875rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.address-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.375rem;
}

.address-label {
  font-size: 0.8125rem;
  font-weight: 700;
  color: #0f172a;
  text-transform: uppercase;
}

.default-badge {
  font-size: 0.6875rem;
  font-weight: 600;
  padding: 0.125rem 0.5rem;
  background: #d1fae5;
  color: #065f46;
  border-radius: 9999px;
}

.address-text {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0;
}

/* Actions Grid */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.25rem 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.action-item:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
  transform: translateY(-2px);
}

.action-icon {
  font-size: 1.75rem;
}

.action-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #0f172a;
}

/* Info Grid */
.info-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.info-value {
  font-size: 0.875rem;
  color: #0f172a;
  font-weight: 600;
}

.btn-outline {
  width: 100%;
  padding: 0.625rem 1rem;
  background: transparent;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-outline:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* Responsive */
@media (max-width: 768px) {
  .welcome-section {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .btn-shop {
    width: 100%;
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
