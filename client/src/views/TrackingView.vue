<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';

const route = useRoute();
const orderId = route.params.id || 1;

const orderData = ref(null);
const loading = ref(true);
const error = ref('');
const isSimulating = ref(false);

// Telemetry state
const currentLat = ref(9.0068);
const currentLng = ref(38.7770);
const currentHeading = ref(315);
const distanceKm = ref(2.4);
const etaMins = ref(9);

let pollTimer = null;
let simulationInterval = null;

async function fetchTracking() {
  try {
    const res = await api.get(`/orders/${orderId}/tracking`);
    orderData.value = res.data;

    if (res.data.telemetry) {
      currentLat.value = res.data.telemetry.latitude;
      currentLng.value = res.data.telemetry.longitude;
      currentHeading.value = res.data.telemetry.heading || 0;
    }
    if (res.data.remaining_distance_km !== null) {
      distanceKm.value = res.data.remaining_distance_km;
      etaMins.value = res.data.eta_minutes;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to fetch order tracking.';
  } finally {
    loading.value = false;
  }
}

// Live Driver Motion Simulator: Watch the car drive in real-time towards customer!
function toggleSimulation() {
  if (isSimulating.value) {
    clearInterval(simulationInterval);
    isSimulating.value = false;
    return;
  }

  isSimulating.value = true;
  const targetLat = orderData.value?.customer_destination?.latitude || 9.0125;
  const targetLng = orderData.value?.customer_destination?.longitude || 38.7698;

  simulationInterval = setInterval(() => {
    // Increment towards customer pin
    const dLat = (targetLat - currentLat.value) * 0.12;
    const dLng = (targetLng - currentLng.value) * 0.12;

    currentLat.value = +(currentLat.value + dLat).toFixed(8);
    currentLng.value = +(currentLng.value + dLng).toFixed(8);

    distanceKm.value = Math.max(0.1, +(distanceKm.value - 0.25).toFixed(2));
    etaMins.value = Math.max(1, Math.round((distanceKm.value / 25) * 60));

    if (distanceKm.value <= 0.15) {
      clearInterval(simulationInterval);
      isSimulating.value = false;
    }
  }, 1400);
}

// Visual Map Position calculation for the radar canvas
const mapPercentages = computed(() => {
  if (!orderData.value) return { store: { x: 20, y: 80 }, cust: { x: 80, y: 20 }, driver: { x: 50, y: 50 } };

  const sLat = orderData.value.store.latitude;
  const sLng = orderData.value.store.longitude;
  const cLat = orderData.value.customer_destination?.latitude || 9.0125;
  const cLng = orderData.value.customer_destination?.longitude || 38.7698;

  const minLat = Math.min(sLat, cLat) - 0.005;
  const maxLat = Math.max(sLat, cLat) + 0.005;
  const minLng = Math.min(sLng, cLng) - 0.005;
  const maxLng = Math.max(sLng, cLng) + 0.005;

  const toPercent = (lat, lng) => ({
    x: Math.min(92, Math.max(8, ((lng - minLng) / (maxLng - minLng)) * 100)),
    y: Math.min(92, Math.max(8, (1 - (lat - minLat) / (maxLat - minLat)) * 100)),
  });

  return {
    store: toPercent(sLat, sLng),
    cust: toPercent(cLat, cLng),
    driver: toPercent(currentLat.value, currentLng.value),
  };
});

onMounted(() => {
  fetchTracking();
  pollTimer = setInterval(fetchTracking, 5000);
});

onUnmounted(() => {
  clearInterval(pollTimer);
  clearInterval(simulationInterval);
});
</script>

<template>
  <div class="tracking-page">
    <div class="app-container">
      <div v-if="loading" class="loading-box">
        <div class="spinner"></div>
        <p>Connecting to courier GPS satellite stream...</p>
      </div>

      <div v-else-if="error" class="error-box card">
        <h3>Could not load tracking</h3>
        <p>{{ error }}</p>
        <button class="btn btn-outline" @click="fetchTracking">Retry</button>
      </div>

      <div v-else class="tracking-layout">
        <!-- TOP: Live ETA & Status Header -->
        <div class="tracking-top-card card">
          <div class="eta-pill-group">
            <div class="live-pulse-badge">
              <span class="live-dot"></span> LIVE RADAR
            </div>
            <div class="eta-headline">
              <span class="eta-number">{{ etaMins }}</span>
              <span class="eta-unit">mins away</span>
            </div>
            <span class="distance-sub">{{ distanceKm }} km remaining to your building</span>
          </div>

          <!-- Handover OTP Code (Yango Safety Handover) -->
          <div class="otp-box">
            <span class="otp-label">🔑 Delivery Handover PIN</span>
            <span class="otp-code">{{ orderData.delivery_otp || '482910' }}</span>
            <span class="otp-hint">Give this 6-digit code to the courier at your door</span>
          </div>
        </div>

        <!-- MIDDLE: Interactive Visual Map Navigator -->
        <div class="map-container card">
          <div class="map-controls-bar">
            <div class="map-title-row">
              <span class="map-tag">Google Maps Turn-by-Turn Engine</span>
              <span class="coords-display">
                Courier Pin: {{ currentLat.toFixed(5) }}, {{ currentLng.toFixed(5) }}
              </span>
            </div>

            <!-- Simulation Toggle Button -->
            <button
              class="btn btn-sm"
              :class="isSimulating ? 'btn-accent' : 'btn-primary'"
              @click="toggleSimulation"
            >
              {{ isSimulating ? '⏸ Pause Driver Movement' : '▶ Simulate Driver Movement' }}
            </button>
          </div>

          <!-- Radar Visual Board -->
          <div class="radar-viewport">
            <div class="grid-overlay"></div>

            <!-- Dynamic Route Polyline SVG -->
            <svg class="route-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
              <!-- Route from store to driver -->
              <line
                :x1="mapPercentages.store.x"
                :y1="mapPercentages.store.y"
                :x2="mapPercentages.driver.x"
                :y2="mapPercentages.driver.y"
                stroke="#10b981"
                stroke-width="1.2"
                stroke-dasharray="2,2"
              />
              <!-- Route from driver to customer destination -->
              <line
                :x1="mapPercentages.driver.x"
                :y1="mapPercentages.driver.y"
                :x2="mapPercentages.cust.x"
                :y2="mapPercentages.cust.y"
                stroke="#059669"
                stroke-width="2.5"
                stroke-linecap="round"
              />
            </svg>

            <!-- Supermarket Store Marker -->
            <div
              class="marker store-marker"
              :style="{ left: `${mapPercentages.store.x}%`, top: `${mapPercentages.store.y}%` }"
            >
              <div class="marker-pin store-pin">🏪</div>
              <span class="marker-label">{{ orderData.store.name }}</span>
            </div>

            <!-- Moving Driver Vehicle Marker -->
            <div
              class="marker driver-marker"
              :style="{
                left: `${mapPercentages.driver.x}%`,
                top: `${mapPercentages.driver.y}%`,
                transform: `translate(-50%, -50%) rotate(${currentHeading}deg)`
              }"
            >
              <div class="car-halo"></div>
              <div class="vehicle-icon">🛵</div>
            </div>

            <!-- Customer Destination Pin Drop -->
            <div
              class="marker cust-marker"
              :style="{ left: `${mapPercentages.cust.x}%`, top: `${mapPercentages.cust.y}%` }"
            >
              <div class="marker-pin cust-pin">🏠</div>
              <span class="marker-label customer-label">
                {{ orderData.customer_destination?.house_villa_no ? `Door #${orderData.customer_destination.house_villa_no}` : 'Your House' }}
              </span>
            </div>
          </div>
        </div>

        <!-- BOTTOM DETAILS: Courier Direct Contact & Granular Address -->
        <div class="bottom-grid">
          <!-- Courier Profile Card with Direct Phone Dial -->
          <div class="detail-card card">
            <div class="card-title-row">
              <h4>🛵 Assigned Delivery Courier</h4>
              <span class="badge badge-green">En Route</span>
            </div>

            <div class="driver-profile-row">
              <div class="driver-avatar">👤</div>
              <div class="driver-info">
                <strong>{{ orderData.driver?.name || 'Yonas Driver' }}</strong>
                <span class="driver-phone">{{ orderData.driver?.phone_number || '+251911000004' }}</span>
                <span class="driver-vehicle">Hero Eco Courier • Addis Ababa</span>
              </div>
            </div>

            <!-- Single-Tap Direct Call Buttons (Yango Style) -->
            <div class="contact-actions">
              <a
                :href="`tel:${orderData.driver?.phone_number || '+251911000004'}`"
                class="btn btn-primary call-btn"
              >
                <span>📞 Call Driver Directly</span>
              </a>
              <a
                :href="`tel:${orderData.store?.phone || '+251116123456'}`"
                class="btn btn-outline call-btn"
              >
                <span>🏢 Call Supermarket</span>
              </a>
            </div>
          </div>

          <!-- Customer Address Details Card -->
          <div class="detail-card card">
            <div class="card-title-row">
              <h4>📍 Destination Address (Exact Drop)</h4>
              <span class="badge badge-dark">Customer Contact</span>
            </div>

            <div class="address-details-body">
              <div class="detail-line">
                <span class="lbl">Recipient Phone:</span>
                <strong class="val highlight-phone">
                  {{ orderData.customer_destination?.customer_phone || '+251911000005' }}
                </strong>
              </div>

              <div class="detail-line">
                <span class="lbl">Building / Villa:</span>
                <span class="val">
                  {{ orderData.customer_destination?.building_name_or_no || 'Standard Building' }},
                  House/Villa #{{ orderData.customer_destination?.house_villa_no }}
                </span>
              </div>

              <div class="detail-line">
                <span class="lbl">Floor & Unit:</span>
                <span class="val">
                  Floor: {{ orderData.customer_destination?.floor_number || 'N/A' }},
                  Apt/Unit: {{ orderData.customer_destination?.apartment_unit || 'N/A' }}
                </span>
              </div>

              <div class="detail-line" v-if="orderData.customer_destination?.gate_code">
                <span class="lbl">Gate / Intercom:</span>
                <strong class="val code-val">
                  {{ orderData.customer_destination?.gate_code }}
                </strong>
              </div>

              <div class="detail-line" v-if="orderData.customer_destination?.nearby_landmark">
                <span class="lbl">Landmark:</span>
                <span class="val landmark-val">
                  🏛️ {{ orderData.customer_destination?.nearby_landmark }}
                </span>
              </div>

              <div class="detail-line" v-if="orderData.customer_destination?.delivery_instructions">
                <span class="lbl">Special Notes:</span>
                <span class="val instructions-val">
                  💬 "{{ orderData.customer_destination?.delivery_instructions }}"
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tracking-page {
  padding: 0 0 3rem;
}

.tracking-layout {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.tracking-top-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
  border: 1.5px solid #a7f3d0;
}

.eta-pill-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.live-pulse-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.72rem;
  font-weight: 800;
  color: #047857;
  letter-spacing: 0.05em;
}

.live-dot {
  width: 9px;
  height: 9px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse 1.4s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.9); opacity: 0.7; }
  50% { transform: scale(1.4); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.7; }
}

.eta-headline {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
}

.eta-number {
  font-size: 2.75rem;
  font-weight: 800;
  color: var(--primary);
  line-height: 1;
}

.eta-unit {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--dark);
}

.distance-sub {
  font-size: 0.85rem;
  color: var(--text-muted);
  font-weight: 500;
}

.otp-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  background: #ffffff;
  padding: 1rem 1.5rem;
  border-radius: var(--radius-md);
  border: 2px dashed #10b981;
}

.otp-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
}

.otp-code {
  font-size: 2rem;
  font-weight: 900;
  letter-spacing: 0.2em;
  color: var(--dark);
  font-family: monospace;
}

.otp-hint {
  font-size: 0.7rem;
  color: var(--text-light);
}

/* Map radar */
.map-container {
  overflow: hidden;
  border: 1.5px solid var(--border-color);
  background: #0f172a;
}

.map-controls-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 1.25rem;
  background: #1e293b;
  border-bottom: 1px solid #334155;
  color: #ffffff;
}

.map-title-row {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.map-tag {
  font-size: 0.825rem;
  font-weight: 700;
  color: #34d399;
}

.coords-display {
  font-size: 0.75rem;
  color: #94a3b8;
  font-family: monospace;
}

.radar-viewport {
  position: relative;
  height: 420px;
  background: radial-gradient(circle at center, #1e293b 0%, #0f172a 100%);
  overflow: hidden;
}

.grid-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: 
    linear-gradient(rgba(52, 211, 153, 0.05) 1px, transparent 1px),
    linear-gradient(90deg, rgba(52, 211, 153, 0.05) 1px, transparent 1px);
  background-size: 32px 32px;
}

.route-svg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.marker {
  position: absolute;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: all 0.5s cubic-bezier(0.2, 0.8, 0.2, 1);
}

.marker-pin {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 4px 15px rgba(0,0,0,0.5);
  border: 2px solid #ffffff;
}

.store-pin {
  background: #3b82f6;
}

.cust-pin {
  background: #ef4444;
}

.marker-label {
  margin-top: 0.35rem;
  background: rgba(15, 23, 42, 0.85);
  color: #ffffff;
  padding: 0.2rem 0.55rem;
  border-radius: var(--radius-sm);
  font-size: 0.72rem;
  font-weight: 700;
  white-space: nowrap;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.customer-label {
  color: #fca5a5;
}

.driver-marker {
  z-index: 10;
}

.car-halo {
  position: absolute;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.25);
  animation: pulse 1.5s infinite;
}

.vehicle-icon {
  width: 46px;
  height: 46px;
  background: #10b981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  border: 2.5px solid #ffffff;
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.8);
}

/* Bottom Grid */
.bottom-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.detail-card {
  padding: 1.5rem;
}

.card-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
}

.card-title-row h4 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--dark);
}

.driver-profile-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.driver-avatar {
  width: 52px;
  height: 52px;
  background: var(--primary-light);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  border: 2px solid #a7f3d0;
}

.driver-info {
  display: flex;
  flex-direction: column;
}

.driver-info strong {
  font-size: 1rem;
  color: var(--dark);
}

.driver-phone {
  font-size: 0.85rem;
  color: var(--primary);
  font-weight: 700;
}

.driver-vehicle {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.contact-actions {
  display: flex;
  gap: 0.75rem;
}

.call-btn {
  flex: 1;
  padding: 0.7rem;
  font-size: 0.85rem;
}

.address-details-body {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.detail-line {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  border-bottom: 1px dashed #f1f5f9;
  padding-bottom: 0.4rem;
}

.detail-line .lbl {
  color: var(--text-muted);
  font-weight: 600;
}

.detail-line .val {
  color: var(--dark);
  font-weight: 600;
  text-align: right;
}

.highlight-phone {
  color: var(--primary);
}

.code-val {
  background: #fef3c7;
  color: #b45309;
  padding: 0.1rem 0.4rem;
  border-radius: var(--radius-sm);
}

.landmark-val {
  color: #b45309;
}

.instructions-val {
  color: #047857;
  font-style: italic;
}

.loading-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 5rem 1rem;
  gap: 1rem;
}

@media (max-width: 800px) {
  .tracking-top-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}
</style>
