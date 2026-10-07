<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const stats = ref({
  total_revenue: 780.00,
  active_orders: 1,
  completed_orders: 0,
  total_orders: 1,
  low_stock_alerts: 2,
});

const recentOrders = ref([]);
const branch = ref(null);
const loading = ref(true);

async function loadManagerStats() {
  loading.value = true;
  try {
    const res = await api.get('/manager/stats');
    stats.value = res.data.stats;
    recentOrders.value = res.data.recent_orders;
    branch.value = res.data.branch;
  } catch (err) {
    console.error('Failed to load manager stats', err);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadManagerStats();
});
</script>

<template>
  <div class="manager-dashboard">
    <!-- Header Title -->
    <div class="dash-head">
      <div>
        <h1 class="dash-title">Store Dashboard</h1>
        <p class="dash-sub">
          Overview of store inventory, live order fulfillment, and daily sales.
        </p>
      </div>

      <div class="head-actions">
        <router-link to="/admin123/inventory" class="btn btn-outline">
          Manage Inventory
        </router-link>
        <router-link to="/admin123/orders" class="btn btn-primary">
          Orders Queue ({{ stats.active_orders }})
        </router-link>
      </div>
    </div>

    <!-- 4 KPI Metrics Cards -->
    <div class="kpi-grid">
      <!-- 1. Revenue -->
      <div class="kpi-card">
        <span class="kpi-lbl">Total Sales</span>
        <strong class="kpi-val">ETB {{ Number(stats.total_revenue).toFixed(2) }}</strong>
        <span class="kpi-meta">Fulfilled store revenue</span>
      </div>

      <!-- 2. Active Orders -->
      <div class="kpi-card">
        <span class="kpi-lbl">Available Orders</span>
        <strong class="kpi-val">{{ stats.active_orders }}</strong>
        <span class="kpi-meta">Orders in active pipeline</span>
      </div>

      <!-- 3. Completed -->
      <div class="kpi-card">
        <span class="kpi-lbl">Delivered Orders</span>
        <strong class="kpi-val">{{ stats.completed_orders || 0 }}</strong>
        <span class="kpi-meta">Completed orders</span>
      </div>

      <!-- 4. Low Stock Alerts -->
      <div class="kpi-card">
        <span class="kpi-lbl">Low Stock Items</span>
        <strong class="kpi-val">{{ stats.low_stock_alerts }}</strong>
        <span class="kpi-meta">Needs replenishment</span>
      </div>
    </div>

    <!-- Active Orders Management Table -->
    <div class="section-card">
      <div class="card-header">
        <div>
          <h3>Live Store Orders Queue</h3>
          <p class="header-sub">Customer orders currently being processed or dispatched.</p>
        </div>
        <router-link to="/admin123/orders" class="view-all-link">
          View All Orders →
        </router-link>
      </div>

      <div class="orders-table-wrapper">
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order Number</th>
              <th>Customer</th>
              <th>Delivery Drop</th>
              <th>Status</th>
              <th>Amount</th>
              <th>Handover PIN</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="recentOrders.length === 0">
              <td colspan="7" class="empty-cell">No active orders right now.</td>
            </tr>
            <tr v-for="order in recentOrders" :key="order.id">
              <td>
                <strong class="ord-num">{{ order.order_number }}</strong>
                <small class="ord-type">{{ order.delivery_type === 'express_immediate' ? 'Express (30m)' : 'Standard Slot' }}</small>
              </td>

              <td>
                <strong class="cust-name">{{ order.customer?.name || 'Marta Shopper' }}</strong>
                <a :href="`tel:${order.delivery_address_snapshot?.customer_phone || order.customer?.phone_number}`" class="cust-phone">
                  📞 {{ order.delivery_address_snapshot?.customer_phone || order.customer?.phone_number }}
                </a>
              </td>

              <td>
                <span class="dest-bldg">
                  {{ order.delivery_address_snapshot?.building_name_or_no || order.delivery_address_snapshot?.street_name }},
                  #{{ order.delivery_address_snapshot?.house_villa_no }}
                </span>
                <small class="dest-sub">{{ order.delivery_address_snapshot?.district_area }}</small>
              </td>

              <td>
                <span
                  class="badge"
                  :class="{
                    'badge-amber': order.status === 'placed' || order.status === 'picking',
                    'badge-blue': order.status === 'packed' || order.status === 'driver_assigned',
                    'badge-green': order.status === 'out_for_delivery' || order.status === 'delivered',
                  }"
                >
                  {{ order.status.replace(/_/g, ' ') }}
                </span>
              </td>

              <td>
                <strong class="amount">ETB {{ Number(order.estimated_total).toFixed(2) }}</strong>
                <small class="pay-method">{{ order.payment_method?.replace(/_/g, ' ') }}</small>
              </td>

              <td>
                <span class="otp-pill">{{ order.delivery_otp || '482910' }}</span>
              </td>

              <td>
                <router-link to="/admin123/orders" class="btn btn-outline btn-sm">
                  Manage
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.manager-dashboard {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.dash-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.dash-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.01em;
}

.dash-sub {
  font-size: 0.825rem;
  color: #6b7280;
  margin-top: 0.2rem;
}

.head-actions {
  display: flex;
  gap: 0.5rem;
}

/* KPI Cards Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}

.kpi-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1rem 1.15rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.kpi-lbl {
  font-size: 0.72rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.kpi-val {
  font-size: 1.35rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.02em;
}

.kpi-meta {
  font-size: 0.75rem;
  color: #9ca3af;
}

/* Section Card & Table */
.section-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
}

.card-header {
  padding: 0.85rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h3 {
  font-size: 0.95rem;
  font-weight: 700;
  color: #111827;
}

.header-sub {
  font-size: 0.78rem;
  color: #6b7280;
}

.view-all-link {
  font-size: 0.8rem;
  font-weight: 600;
  color: #111827;
}
.view-all-link:hover {
  text-decoration: underline;
}

.orders-table-wrapper {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.orders-table th {
  padding: 0.65rem 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  letter-spacing: 0.04em;
}

.orders-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.825rem;
  vertical-align: middle;
}

.orders-table tr:last-child td {
  border-bottom: none;
}

.ord-num {
  display: block;
  font-size: 0.85rem;
  color: #111827;
}

.ord-type {
  font-size: 0.7rem;
  color: #6b7280;
}

.cust-name {
  display: block;
  font-size: 0.825rem;
  color: #111827;
}

.cust-phone {
  font-size: 0.75rem;
  color: #4b5563;
  display: inline-block;
}
.cust-phone:hover {
  color: #111827;
  text-decoration: underline;
}

.dest-bldg {
  display: block;
  font-size: 0.825rem;
  color: #374151;
}

.dest-sub {
  font-size: 0.72rem;
  color: #9ca3af;
}

.amount {
  display: block;
  font-size: 0.85rem;
  color: #111827;
}

.pay-method {
  font-size: 0.7rem;
  color: #6b7280;
  text-transform: capitalize;
}

.otp-pill {
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
  background: #f3f4f6;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  color: #111827;
  border: 1px solid #e5e7eb;
}

.empty-cell {
  text-align: center;
  padding: 2.5rem !important;
  color: #9ca3af;
}

@media (max-width: 900px) {
  .kpi-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
