<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const activeOrder = ref(null);
const loading = ref(true);
const items = ref([]);
const isPacked = ref(false);

async function loadPickerData() {
  loading.value = true;
  try {
    const res = await api.get('/orders/1');
    activeOrder.value = res.data;
    items.value = res.data.items.map((i) => ({
      ...i,
      is_picked: true,
      actual_weight: i.picked_quantity || i.requested_quantity,
    }));
  } catch (err) {
    console.error('Failed to load picker order', err);
  } finally {
    loading.value = false;
  }
}

function toggleItemPicked(item) {
  item.is_picked = !item.is_picked;
}

function completePacking() {
  isPacked.value = true;
}

onMounted(() => {
  loadPickerData();
});
</script>

<template>
  <div class="picker-page">
    <div class="picker-header card">
      <div class="header-main">
        <div class="station-badge">📦 IN-STORE PICKING WORKSTATION</div>
        <h2>Active Order: {{ activeOrder?.order_number || 'ORD-20261007-DEMO1' }}</h2>
        <span class="sub-meta">
          Branch: Bole Medhanialem Flagship • Customer: Marta Shopper ({{ activeOrder?.delivery_address_snapshot?.customer_phone || '+251911000005' }})
        </span>
      </div>

      <div class="status-box">
        <span class="badge" :class="isPacked ? 'badge-green' : 'badge-amber'">
          {{ isPacked ? '✅ Ready for Courier Handover' : '⏳ Picking In Progress' }}
        </span>
        <span class="items-count">{{ items.filter(i => i.is_picked).length }} of {{ items.length }} Items Packed</span>
      </div>
    </div>

    <!-- Pick List Table -->
    <div class="picklist-card card">
      <div class="table-header-row">
        <span>Item & Shelf Details</span>
        <span>Requested Qty</span>
        <span>Scale / Picked Qty</span>
        <span>Substitution Rule</span>
        <span>Status</span>
      </div>

      <div class="picklist-body">
        <div
          v-for="item in items"
          :key="item.id"
          class="pick-item-row"
          :class="{ picked: item.is_picked }"
        >
          <!-- Item & Shelf -->
          <div class="item-col">
            <input
              type="checkbox"
              :checked="item.is_picked"
              @change="toggleItemPicked(item)"
              class="pick-checkbox"
            />
            <img :src="item.product?.image_url" class="pick-img" />
            <div>
              <strong class="item-name">{{ item.product?.name }}</strong>
              <small class="sku-tag">SKU: {{ item.product?.sku }} • Shelf: Aisle 3</small>
            </div>
          </div>

          <!-- Requested Qty -->
          <div class="qty-col">
            <span class="qty-pill">
              {{ item.requested_quantity }} {{ item.product?.unit_of_measure }}
            </span>
            <small v-if="item.product?.pricing_type === 'weighted'" class="weighted-flag">
              ⚖️ Weighted Item
            </small>
          </div>

          <!-- Actual Scale Weight Input -->
          <div class="scale-col">
            <div v-if="item.product?.pricing_type === 'weighted'" class="scale-input-box">
              <span class="scale-icon">⚖️</span>
              <input
                type="number"
                step="0.01"
                v-model.number="item.actual_weight"
                class="scale-input"
              />
              <span class="scale-unit">kg</span>
            </div>
            <div v-else class="unit-check">
              <span>{{ item.requested_quantity }} {{ item.product?.unit_of_measure }}</span>
            </div>
          </div>

          <!-- Customer Substitution Rule -->
          <div class="sub-col">
            <span
              class="sub-badge"
              :class="{
                'sub-match': item.substitution_preference === 'best_match',
                'sub-call': item.substitution_preference === 'contact_customer',
                'sub-none': item.substitution_preference === 'do_not_substitute'
              }"
            >
              <template v-if="item.substitution_preference === 'best_match'">
                🔄 Best Match
              </template>
              <template v-else-if="item.substitution_preference === 'contact_customer'">
                📞 Call Customer
              </template>
              <template v-else>
                ❌ Do Not Replace
              </template>
            </span>
          </div>

          <!-- Action -->
          <div class="action-col">
            <span v-if="item.is_picked" class="badge badge-green">✓ Picked</span>
            <span v-else class="badge badge-dark">Pending</span>
          </div>
        </div>
      </div>

      <div class="card-footer">
        <div class="footer-left">
          <strong>Cold Chain & Packaging Note:</strong>
          <span>Pack perishables and frozen dairy products into insulated thermal bags.</span>
        </div>
        <button
          class="btn btn-primary finish-btn"
          :disabled="isPacked"
          @click="completePacking"
        >
          {{ isPacked ? 'Order Packed & Ready' : 'Confirm Picking Complete & Notify Courier →' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.picker-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.75rem;
  background: #ffffff;
}

.station-badge {
  font-size: 0.72rem;
  font-weight: 800;
  color: var(--accent);
  letter-spacing: 0.05em;
  margin-bottom: 0.35rem;
}

.picker-header h2 {
  font-size: 1.45rem;
  font-weight: 800;
  color: var(--dark);
}

.sub-meta {
  font-size: 0.85rem;
  color: var(--text-muted);
}

.status-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.35rem;
}

.items-count {
  font-size: 0.825rem;
  color: var(--text-muted);
  font-weight: 600;
}

.picklist-card {
  padding: 0;
  overflow: hidden;
}

.table-header-row {
  display: grid;
  grid-template-columns: 2.2fr 1fr 1.2fr 1.2fr 0.8fr;
  padding: 0.85rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--border-color);
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
}

.picklist-body {
  display: flex;
  flex-direction: column;
}

.pick-item-row {
  display: grid;
  grid-template-columns: 2.2fr 1fr 1.2fr 1.2fr 0.8fr;
  align-items: center;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  transition: var(--transition);
}

.pick-item-row.picked {
  background: #f0fdf4;
}

.item-col {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pick-checkbox {
  width: 20px;
  height: 20px;
  accent-color: var(--primary);
  cursor: pointer;
}

.pick-img {
  width: 48px;
  height: 48px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: #f1f5f9;
}

.item-name {
  display: block;
  font-size: 0.95rem;
  color: var(--dark);
}

.sku-tag {
  font-size: 0.72rem;
  color: var(--text-muted);
}

.qty-col {
  display: flex;
  flex-direction: column;
}

.qty-pill {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--dark);
}

.weighted-flag {
  font-size: 0.7rem;
  color: #b45309;
}

.scale-input-box {
  display: inline-flex;
  align-items: center;
  background: #ffffff;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.25rem 0.5rem;
  gap: 0.35rem;
}

.scale-input {
  width: 60px;
  border: none;
  font-weight: 800;
  font-size: 0.9rem;
  color: var(--dark);
  text-align: center;
}

.scale-unit {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-weight: 700;
}

.sub-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.55rem;
  border-radius: var(--radius-sm);
}

.sub-match {
  background: #eff6ff;
  color: #1d4ed8;
}

.sub-call {
  background: #fef3c7;
  color: #b45309;
}

.sub-none {
  background: #fee2e2;
  color: #b91c1c;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: #f8fafc;
}

.footer-left strong {
  display: block;
  font-size: 0.825rem;
  color: var(--dark);
}

.footer-left span {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.finish-btn {
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
}
</style>
