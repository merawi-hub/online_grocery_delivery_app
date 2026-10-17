<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import api from '../../services/api';

const route = useRoute();
const orders = ref([]);
const activeSection = ref(route.query.tab === 'delivered' ? 'delivered' : 'available');
const subStatusFilter = ref('all');
const searchQuery = ref('');
const loading = ref(true);
const updatingId = ref(null);
const feedbackMsg = ref('');

watch(
  () => route.query.tab,
  (newTab) => {
    if (newTab === 'delivered') {
      activeSection.value = 'delivered';
    } else {
      activeSection.value = 'available';
    }
  }
);

async function loadOrders() {
  loading.value = true;
  try {
    const res = await api.get('/manager/orders');
    orders.value = res.data;
  } catch (err) {
    console.error('Failed to load orders', err);
  } finally {
    loading.value = false;
  }
}

// Split orders by Available (Active) vs Delivered
const availableOrders = computed(() => {
  return orders.value.filter((o) => o.status !== 'delivered' && o.status !== 'cancelled');
});

const deliveredOrders = computed(() => {
  return orders.value.filter((o) => o.status === 'delivered');
});

const currentList = computed(() => {
  const base = activeSection.value === 'available' ? availableOrders.value : deliveredOrders.value;

  return base.filter((order) => {
    const matchesStatus = subStatusFilter.value === 'all' || order.status === subStatusFilter.value;
    const q = searchQuery.value.toLowerCase();
    const matchesSearch = !q ||
      order.order_number.toLowerCase().includes(q) ||
      (order.customer?.name && order.customer.name.toLowerCase().includes(q)) ||
      (order.delivery_address_snapshot?.customer_phone && order.delivery_address_snapshot.customer_phone.includes(q));

    return matchesStatus && matchesSearch;
  });
});

async function advanceOrderStatus(order, nextStatus) {
  updatingId.value = order.id;
  try {
    await api.patch(`/manager/orders/${order.id}/status`, { status: nextStatus });
    order.status = nextStatus;
    feedbackMsg.value = `Order ${order.order_number} status updated to ${nextStatus.replace(/_/g, ' ')}`;
    setTimeout(() => { feedbackMsg.value = ''; }, 3000);
  } catch (err) {
    console.error('Failed to advance order status', err);
  } finally {
    updatingId.value = null;
  }
}

onMounted(() => {
  loadOrders();
});
</script>

<template>
  <div class="manager-orders-page">
    <!-- Header -->
    <div class="page-head">
      <div>
        <h1 class="page-title">Orders Management</h1>
        <p class="page-sub">
          Manage real-time grocery orders, picking queue, and delivery fulfillment.
        </p>
      </div>

      <div class="head-actions">
        <router-link to="/admin123/inventory" class="btn btn-outline">
          <span>📦</span> Manage Inventory
        </router-link>
        <router-link to="/admin123/dashboard" class="btn btn-outline">
          <span>📊</span> Dashboard
        </router-link>
        <button class="btn btn-primary">
          <span>➕</span> Add Product
        </button>
      </div>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="quick-nav-bar">
      <button
        class="nav-btn"
        :class="{ active: activeSection === 'available' && subStatusFilter === 'all' }"
        @click="activeSection = 'available'; subStatusFilter = 'all';"
      >
        <span class="nav-icon">📥</span>
        <div class="nav-content">
          <span class="nav-label">Available Orders</span>
          <span class="nav-count">{{ availableOrders.length }}</span>
        </div>
      </button>

      <button
        class="nav-btn"
        :class="{ active: activeSection === 'delivered' }"
        @click="activeSection = 'delivered'; subStatusFilter = 'all';"
      >
        <span class="nav-icon">✅</span>
        <div class="nav-content">
          <span class="nav-label">Delivered Orders</span>
          <span class="nav-count">{{ deliveredOrders.length }}</span>
        </div>
      </button>

      <button
        class="nav-btn"
        :class="{ active: activeSection === 'available' && subStatusFilter === 'placed' }"
        @click="activeSection = 'available'; subStatusFilter = 'placed';"
      >
        <span class="nav-icon">🆕</span>
        <div class="nav-content">
          <span class="nav-label">New Orders</span>
          <span class="nav-count">{{ availableOrders.filter(o => o.status === 'placed').length }}</span>
        </div>
      </button>

      <button
        class="nav-btn"
        :class="{ active: activeSection === 'available' && subStatusFilter === 'picking' }"
        @click="activeSection = 'available'; subStatusFilter = 'picking';"
      >
        <span class="nav-icon">📋</span>
        <div class="nav-content">
          <span class="nav-label">Picking Queue</span>
          <span class="nav-count">{{ availableOrders.filter(o => o.status === 'picking').length }}</span>
        </div>
      </button>

      <button
        class="nav-btn"
        :class="{ active: activeSection === 'available' && subStatusFilter === 'packed' }"
        @click="activeSection = 'available'; subStatusFilter = 'packed';"
      >
        <span class="nav-icon">📦</span>
        <div class="nav-content">
          <span class="nav-label">Packed & Ready</span>
          <span class="nav-count">{{ availableOrders.filter(o => o.status === 'packed').length }}</span>
        </div>
      </button>

      <button
        class="nav-btn"
        :class="{ active: activeSection === 'available' && subStatusFilter === 'out_for_delivery' }"
        @click="activeSection = 'available'; subStatusFilter = 'out_for_delivery';"
      >
        <span class="nav-icon">🚚</span>
        <div class="nav-content">
          <span class="nav-label">Out for Delivery</span>
          <span class="nav-count">{{ availableOrders.filter(o => o.status === 'out_for_delivery').length }}</span>
        </div>
      </button>
    </div>

    <!-- Alert Banner -->
    <div v-if="feedbackMsg" class="success-banner">
      ✓ {{ feedbackMsg }}
    </div>

    <!-- Navigation Tabs: Available Orders vs Delivered Orders -->
    <div class="tabs-container">
      <div class="main-tabs">
        <button
          class="tab-btn"
          :class="{ active: activeSection === 'available' }"
          @click="activeSection = 'available'; subStatusFilter = 'all';"
        >
          Available Orders ({{ availableOrders.length }})
        </button>

        <button
          class="tab-btn"
          :class="{ active: activeSection === 'delivered' }"
          @click="activeSection = 'delivered'; subStatusFilter = 'all';"
        >
          Delivered Orders ({{ deliveredOrders.length }})
        </button>
      </div>

      <!-- Controls Row -->
      <div class="controls-row">
        <div class="search-wrap">
          <span class="search-icon">🔍</span>
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search order number, customer, or phone..."
          />
          <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''">✕</button>
        </div>

        <div v-if="activeSection === 'available'" class="status-filters">
          <button
            class="filter-btn"
            :class="{ active: subStatusFilter === 'all' }"
            @click="subStatusFilter = 'all'"
          >
            All
          </button>
          <button
            class="filter-btn"
            :class="{ active: subStatusFilter === 'placed' }"
            @click="subStatusFilter = 'placed'"
          >
            New
          </button>
          <button
            class="filter-btn"
            :class="{ active: subStatusFilter === 'picking' }"
            @click="subStatusFilter = 'picking'"
          >
            Picking
          </button>
          <button
            class="filter-btn"
            :class="{ active: subStatusFilter === 'packed' }"
            @click="subStatusFilter = 'packed'"
          >
            Packed
          </button>
          <button
            class="filter-btn"
            :class="{ active: subStatusFilter === 'out_for_delivery' }"
            @click="subStatusFilter = 'out_for_delivery'"
          >
            Out for Delivery
          </button>
        </div>
      </div>
    </div>

    <!-- Orders Feed -->
    <div v-if="loading" class="empty-state">
      Loading orders queue...
    </div>

    <div v-else-if="currentList.length === 0" class="empty-state">
      {{ activeSection === 'available' ? 'No active orders currently waiting in this queue.' : 'No delivered orders in this archive.' }}
    </div>

    <div v-else class="orders-list">
      <div v-for="order in currentList" :key="order.id" class="order-box">
        <!-- Order Header -->
        <div class="order-box-header">
          <div class="order-meta-left">
            <strong class="order-number">{{ order.order_number }}</strong>
            <span class="delivery-type-tag">
              {{ order.delivery_type === 'express_immediate' ? 'Express (30m)' : 'Scheduled Slot' }}
            </span>
            <span class="order-time">
              {{ new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
            </span>
          </div>

          <div class="order-meta-right">
            <div class="otp-tag">
              <span>PIN:</span>
              <strong>{{ order.delivery_otp || '482910' }}</strong>
            </div>

            <span
              class="badge"
              :class="{
                'badge-amber': order.status === 'placed' || order.status === 'picking',
                'badge-blue': order.status === 'packed',
                'badge-green': order.status === 'out_for_delivery' || order.status === 'delivered',
              }"
            >
              {{ order.status.replace(/_/g, ' ') }}
            </span>
          </div>
        </div>

        <!-- Order Information Grid -->
        <div class="order-body">
          <div class="info-column">
            <span class="info-label">Customer</span>
            <strong class="customer-name">{{ order.customer?.name || 'Marta Shopper' }}</strong>
            <a
              :href="`tel:${order.delivery_address_snapshot?.customer_phone || order.customer?.phone_number || '+251911000005'}`"
              class="phone-link"
            >
              📞 {{ order.delivery_address_snapshot?.customer_phone || order.customer?.phone_number || '+251 91 100 0005' }}
            </a>
            <div class="payment-info">
              <span>Payment: {{ order.payment_method?.replace(/_/g, ' ') }}</span>
            </div>
          </div>

          <div class="info-column">
            <span class="info-label">Delivery Address</span>
            <strong class="address-main">
              {{ order.delivery_address_snapshot?.building_name_or_no || order.delivery_address_snapshot?.street_name }},
              #{{ order.delivery_address_snapshot?.house_villa_no }}
            </strong>
            <span class="address-sub">{{ order.delivery_address_snapshot?.district_area || 'Bole, Addis Ababa' }}</span>
          </div>

          <div class="info-column">
            <span class="info-label">Order Items ({{ order.items?.length || 0 }})</span>
            <div class="items-list">
              <div v-for="item in (order.items || []).slice(0, 3)" :key="item.id" class="item-line">
                <span>{{ Number(item.quantity) }}x {{ item.product_name }}</span>
                <span class="item-price">ETB {{ Number(item.subtotal).toFixed(2) }}</span>
              </div>
              <small v-if="(order.items || []).length > 3" class="more-items">
                +{{ order.items.length - 3 }} more items
              </small>
            </div>
          </div>

          <div class="info-column total-col">
            <span class="info-label">Total Amount</span>
            <strong class="order-total">ETB {{ Number(order.estimated_total).toFixed(2) }}</strong>
          </div>
        </div>

        <!-- Order Action Controls -->
        <div class="order-box-footer" v-if="order.status !== 'delivered'">
          <div class="actions-left">
            <span class="pipeline-hint">Current stage: <strong>{{ order.status.replace(/_/g, ' ') }}</strong></span>
          </div>

          <div class="actions-right">
            <!-- Progression Buttons -->
            <button
              v-if="order.status === 'placed'"
              class="btn btn-primary"
              :disabled="updatingId === order.id"
              @click="advanceOrderStatus(order, 'picking')"
            >
              Start Picking
            </button>

            <button
              v-else-if="order.status === 'picking'"
              class="btn btn-primary"
              :disabled="updatingId === order.id"
              @click="advanceOrderStatus(order, 'packed')"
            >
              Mark Packed
            </button>

            <button
              v-else-if="order.status === 'packed'"
              class="btn btn-primary"
              :disabled="updatingId === order.id"
              @click="advanceOrderStatus(order, 'out_for_delivery')"
            >
              Dispatch for Delivery
            </button>

            <button
              v-else-if="order.status === 'out_for_delivery'"
              class="btn btn-primary"
              :disabled="updatingId === order.id"
              @click="advanceOrderStatus(order, 'delivered')"
            >
              Complete Delivery
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.manager-orders-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.page-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.01em;
}

.page-sub {
  font-size: 0.825rem;
  color: #6b7280;
  margin-top: 0.2rem;
}

.head-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid #e5e7eb;
  background: white;
  color: #111827;
  transition: all 0.2s;
  text-decoration: none;
}

.btn:hover {
  background: #f8fafc;
  border-color: #0f172a;
}

.btn-outline {
  background: white;
  border: 1px solid #e5e7eb;
  color: #475569;
}

.btn-outline:hover {
  background: #f8fafc;
  border-color: #0f172a;
  color: #0f172a;
}

.btn-primary {
  background: #0f172a;
  color: white;
  border-color: #0f172a;
}

.btn-primary:hover {
  background: #1e293b;
}

/* Quick Navigation Bar */
.quick-nav-bar {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 0.75rem;
  background: white;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.nav-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.nav-btn.active {
  background: #0f172a;
  border-color: #0f172a;
}

.nav-btn.active .nav-icon {
  filter: grayscale(0) brightness(2);
}

.nav-btn.active .nav-label {
  color: white;
}

.nav-btn.active .nav-count {
  background: white;
  color: #0f172a;
}

.nav-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.nav-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.nav-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.2;
}

.nav-count {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  background: white;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  display: inline-block;
  align-self: flex-start;
}

.success-banner {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
  padding: 0.65rem 0.85rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Tabs & Controls */
.tabs-container {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.main-tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.tab-btn {
  padding: 0.65rem 1.25rem;
  font-size: 0.825rem;
  font-weight: 500;
  color: #6b7280;
  background: none;
  border: none;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  color: #111827;
}

.tab-btn.active {
  color: #111827;
  font-weight: 600;
  background: #ffffff;
  border-bottom-color: #111827;
}

.controls-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 0.85rem;
  gap: 1rem;
}

.search-wrap {
  display: flex;
  align-items: center;
  flex: 1;
  max-width: 420px;
}

.search-icon {
  font-size: 0.85rem;
  color: #9ca3af;
  margin-right: 0.4rem;
}

.search-wrap input {
  border: none;
  background: transparent;
  width: 100%;
  font-size: 0.85rem;
  color: #111827;
}

.clear-btn {
  font-size: 0.75rem;
  color: #9ca3af;
  cursor: pointer;
}

.clear-btn:hover {
  color: #111827;
}

.status-filters {
  display: flex;
  gap: 0.25rem;
}

.filter-btn {
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #4b5563;
  cursor: pointer;
}

.filter-btn:hover {
  background: #f9fafb;
  color: #111827;
}

.filter-btn.active {
  background: #111827;
  color: #ffffff;
  border-color: #111827;
}

/* Orders List */
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.order-box {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.order-box-header {
  padding: 0.65rem 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.order-meta-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.order-number {
  font-size: 0.9rem;
  color: #111827;
}

.delivery-type-tag {
  font-size: 0.7rem;
  color: #4b5563;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}

.order-time {
  font-size: 0.72rem;
  color: #9ca3af;
}

.order-meta-right {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.otp-tag {
  font-size: 0.75rem;
  color: #4b5563;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.otp-tag strong {
  font-family: monospace;
  color: #111827;
  background: #f3f4f6;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
}

.order-body {
  padding: 0.85rem 1rem;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.info-column {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-label {
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #9ca3af;
  letter-spacing: 0.04em;
}

.customer-name {
  font-size: 0.825rem;
  color: #111827;
}

.phone-link {
  font-size: 0.75rem;
  color: #4b5563;
}

.phone-link:hover {
  color: #111827;
  text-decoration: underline;
}

.payment-info {
  font-size: 0.72rem;
  color: #6b7280;
  text-transform: capitalize;
}

.address-main {
  font-size: 0.825rem;
  color: #111827;
}

.address-sub {
  font-size: 0.72rem;
  color: #6b7280;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.item-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #4b5563;
}

.item-price {
  color: #111827;
  font-weight: 500;
}

.more-items {
  font-size: 0.7rem;
  color: #9ca3af;
}

.total-col {
  text-align: right;
}

.order-total {
  font-size: 1.15rem;
  color: #111827;
  font-weight: 700;
}

.order-box-footer {
  padding: 0.55rem 1rem;
  border-top: 1px solid #e5e7eb;
  background: #ffffff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pipeline-hint {
  font-size: 0.75rem;
  color: #6b7280;
}

.pipeline-hint strong {
  color: #111827;
  text-transform: capitalize;
}

.empty-state {
  text-align: center;
  padding: 3rem !important;
  color: #9ca3af;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.85rem;
}
</style>
