<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const activeOrder = ref(null);
const loading = ref(true);
const enteredOtp = ref('');
const isDelivered = ref(false);
const otpError = ref('');

async function loadDriverData() {
  loading.value = true;
  try {
    const res = await api.get('/orders/1/tracking');
    activeOrder.value = res.data;
  } catch (err) {
    console.error('Failed to load driver trip', err);
  } finally {
    loading.value = false;
  }
}

function verifyHandover() {
  otpError.value = '';
  const expected = activeOrder.value?.delivery_otp || '482910';
  if (enteredOtp.value === expected) {
    isDelivered.value = true;
  } else {
    otpError.value = 'Invalid OTP code. Please ask customer for their 6-digit handover PIN.';
  }
}

onMounted(() => {
  loadDriverData();
});
</script>

<template>
  <div class="driver-page">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading dispatch trip data...</p>
    </div>

    <template v-else>
      <!-- Top Active Trip Card -->
      <div class="trip-hero card">
        <div class="trip-main">
          <div class="dispatch-tag">🛵 ACTIVE COURIER DELIVERY RUN</div>
          <h2>Order #{{ activeOrder?.order_number || 'ORD-20261007-DEMO1' }}</h2>
          <div class="trip-metrics">
            <span class="metric">📍 2.4 km away</span>
            <span class="metric-dot">•</span>
            <span class="metric">⏱ ~9 mins drive</span>
            <span class="metric-dot">•</span>
            <span class="metric">💵 ETB 780.00 (Cash to collect)</span>
          </div>
        </div>

        <div class="action-buttons">
          <!-- 1-Tap Customer Call (Yango Style) -->
          <a
            :href="`tel:${activeOrder?.customer_destination?.customer_phone || '+251911000005'}`"
            class="btn btn-primary call-btn"
          >
            <span>📞 Call Customer (Marta)</span>
          </a>

          <!-- Native Google Maps Navigation Intent -->
          <a
            :href="`https://www.google.com/maps/dir/?api=1&destination=${activeOrder?.customer_destination?.latitude || 9.0125},${activeOrder?.customer_destination?.longitude || 38.7698}`"
            target="_blank"
            class="btn btn-outline nav-btn"
          >
            <span>🧭 Open Google Maps GPS</span>
          </a>
        </div>
      </div>

      <!-- Two-Column Courier Console -->
      <div class="console-grid">
        <!-- LEFT: Granular Building & Gate Details -->
        <div class="detail-card card">
          <div class="card-header">
            <h3>🏢 Exact Customer Drop Location</h3>
            <span class="badge badge-green">GPS Verified</span>
          </div>

          <div class="address-sheet">
            <div class="address-highlight-box">
              <span class="house-badge">House / Villa #{{ activeOrder?.customer_destination?.house_villa_no || 'B-42' }}</span>
              <h4 class="bldg-name">{{ activeOrder?.customer_destination?.building_name_or_no || 'Sunrise Luxury Apartments' }}</h4>
              <p class="floor-unit">
                Floor: <strong>{{ activeOrder?.customer_destination?.floor_number || '4th Floor' }}</strong> •
                Unit/Apt: <strong>{{ activeOrder?.customer_destination?.apartment_unit || 'Apt 402' }}</strong>
              </p>
            </div>

            <div class="spec-row">
              <span class="spec-label">Street & District:</span>
              <span class="spec-val">
                {{ activeOrder?.customer_destination?.street_name || 'Menelik II Ave' }},
                {{ activeOrder?.customer_destination?.district_area || 'Kirkos / Kazanchis' }}
              </span>
            </div>

            <div class="spec-row" v-if="activeOrder?.customer_destination?.gate_code">
              <span class="spec-label">🔑 Security Gate Code:</span>
              <span class="spec-val code-highlight">
                {{ activeOrder?.customer_destination?.gate_code }}
              </span>
            </div>

            <div class="spec-row" v-if="activeOrder?.customer_destination?.nearby_landmark">
              <span class="spec-label">🏛️ Visual Landmark:</span>
              <span class="spec-val landmark-text">
                {{ activeOrder?.customer_destination?.nearby_landmark }}
              </span>
            </div>

            <div class="spec-row" v-if="activeOrder?.customer_destination?.delivery_instructions">
              <span class="spec-label">💬 Delivery Instructions:</span>
              <span class="spec-val instructions-text">
                "{{ activeOrder?.customer_destination?.delivery_instructions }}"
              </span>
            </div>
          </div>
        </div>

        <!-- RIGHT: Handover OTP Verification & Proof of Delivery -->
        <div class="detail-card card">
          <div class="card-header">
            <h3>🔑 Handover Proof of Delivery</h3>
            <span class="badge" :class="isDelivered ? 'badge-green' : 'badge-amber'">
              {{ isDelivered ? 'Delivered' : 'Pending Handover' }}
            </span>
          </div>

          <div v-if="!isDelivered" class="otp-verification-box">
            <p class="otp-instruction">
              Ask the recipient for their <strong>6-digit handover PIN</strong> before releasing the grocery crates.
            </p>

            <div v-if="otpError" class="error-banner">{{ otpError }}</div>

            <div class="otp-input-group">
              <input
                type="text"
                maxlength="6"
                v-model="enteredOtp"
                placeholder="Enter 6-digit PIN"
                class="otp-digit-input"
              />
              <button class="btn btn-primary verify-btn" @click="verifyHandover">
                Verify & Complete Handover ✓
              </button>
            </div>

            <div class="hint-box">
              <small>Demo Hint: Customer's PIN is <strong>{{ activeOrder?.delivery_otp || '482910' }}</strong></small>
            </div>
          </div>

          <div v-else class="success-handover-box">
            <div class="success-icon">🎉</div>
            <h4>Handover Verified & Completed!</h4>
            <p>Groceries successfully received by customer. Cash collected: ETB 780.00.</p>
            <span class="badge badge-green">Trip Closed</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.driver-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.trip-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.75rem 2rem;
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
  border: 1.5px solid #a7f3d0;
}

.dispatch-tag {
  font-size: 0.72rem;
  font-weight: 800;
  color: var(--primary);
  letter-spacing: 0.05em;
  margin-bottom: 0.35rem;
}

.trip-hero h2 {
  font-size: 1.55rem;
  font-weight: 800;
  color: var(--dark);
  margin-bottom: 0.4rem;
}

.trip-metrics {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 600;
}

.metric-dot {
  color: #cbd5e1;
}

.action-buttons {
  display: flex;
  gap: 0.75rem;
}

.call-btn {
  padding: 0.75rem 1.25rem;
  font-size: 0.9rem;
}

.nav-btn {
  padding: 0.75rem 1.25rem;
  font-size: 0.9rem;
}

.console-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1.5rem;
}

.detail-card {
  padding: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
}

.card-header h3 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--dark);
}

.address-sheet {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.address-highlight-box {
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 1rem 1.25rem;
}

.house-badge {
  display: inline-block;
  background: var(--dark);
  color: #ffffff;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: var(--radius-sm);
  margin-bottom: 0.45rem;
}

.bldg-name {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--dark);
}

.floor-unit {
  font-size: 0.85rem;
  color: var(--text-muted);
  margin-top: 0.2rem;
}

.spec-row {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  font-size: 0.85rem;
  border-bottom: 1px dashed #f1f5f9;
  padding-bottom: 0.65rem;
}

.spec-label {
  font-size: 0.72rem;
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
}

.spec-val {
  color: var(--dark);
  font-weight: 600;
}

.code-highlight {
  font-size: 1rem;
  color: #b45309;
  font-family: monospace;
  font-weight: 800;
}

.landmark-text {
  color: #b45309;
}

.instructions-text {
  color: #047857;
  font-style: italic;
}

.otp-verification-box {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1rem 0;
}

.otp-instruction {
  font-size: 0.85rem;
  color: var(--text-muted);
  line-height: 1.5;
}

.otp-input-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.otp-digit-input {
  text-align: center;
  font-size: 1.75rem;
  letter-spacing: 0.25em;
  font-weight: 900;
  font-family: monospace;
  padding: 0.75rem;
  border: 2px dashed var(--primary);
  border-radius: var(--radius-md);
  background: #f0fdf4;
}

.verify-btn {
  padding: 0.85rem;
  font-size: 0.95rem;
}

.hint-box {
  background: #f8fafc;
  padding: 0.5rem 0.75rem;
  border-radius: var(--radius-sm);
  text-align: center;
  color: var(--text-muted);
}

.success-handover-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2.5rem 1rem;
  text-align: center;
  gap: 0.75rem;
}

.success-icon {
  font-size: 3rem;
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.65rem;
  border-radius: var(--radius-sm);
  font-size: 0.825rem;
}

@media (max-width: 900px) {
  .trip-hero {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .console-grid {
    grid-template-columns: 1fr;
  }
}
</style>
