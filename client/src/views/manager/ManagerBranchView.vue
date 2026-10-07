<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';

const branch = ref({
  name: 'Bole Medhanialem Flagship Branch',
  address: 'Bole Sub-City, Next to Edna Mall, Addis Ababa',
  contact_phone: '+251 11 612 3456',
  delivery_radius_km: 10.0,
  opening_time: '07:30',
  closing_time: '22:30',
  is_open: true,
  latitude: 9.0016,
  longitude: 38.7845,
});

const isSaving = ref(false);
const saveSuccess = ref(false);

async function loadBranch() {
  try {
    const res = await api.get('/manager/stats');
    if (res.data.branch) {
      branch.value = {
        ...res.data.branch,
        opening_time: res.data.branch.opening_time?.slice(0, 5) || '07:30',
        closing_time: res.data.branch.closing_time?.slice(0, 5) || '22:30',
      };
    }
  } catch (err) {
    console.error('Failed to load branch settings', err);
  }
}

async function saveSettings() {
  isSaving.value = true;
  saveSuccess.value = false;
  try {
    await api.put('/manager/branch', {
      name: branch.value.name,
      contact_phone: branch.value.contact_phone,
      delivery_radius_km: branch.value.delivery_radius_km,
      is_open: branch.value.is_open,
      opening_time: branch.value.opening_time,
      closing_time: branch.value.closing_time,
    });
    saveSuccess.value = true;
    setTimeout(() => { saveSuccess.value = false; }, 3000);
  } catch (err) {
    console.error('Failed to save branch settings', err);
  } finally {
    isSaving.value = false;
  }
}

onMounted(() => {
  loadBranch();
});
</script>

<template>
  <div class="manager-branch-page">
    <div class="page-head">
      <h1 class="page-title">Store Settings & Operations</h1>
      <p class="page-sub">
        Configure store profile, operational hours, contact phone, and dispatch radius.
      </p>
    </div>

    <div v-if="saveSuccess" class="success-banner">
      ✓ Store settings saved successfully.
    </div>

    <div class="settings-grid">
      <!-- Store Details Form -->
      <form class="settings-box" @submit.prevent="saveSettings">
        <h3 class="box-title">Store Profile</h3>

        <div class="form-group">
          <label>Store Name *</label>
          <input type="text" v-model="branch.name" required class="form-input" />
        </div>

        <div class="form-group">
          <label>Store Physical Address *</label>
          <input type="text" v-model="branch.address" required class="form-input" />
        </div>

        <div class="form-row">
          <div class="form-group flex-1">
            <label>Contact Phone *</label>
            <input type="text" v-model="branch.contact_phone" required class="form-input" />
          </div>

          <div class="form-group flex-1">
            <label>Delivery Radius (km) *</label>
            <input type="number" step="0.5" min="1" max="50" v-model.number="branch.delivery_radius_km" required class="form-input" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group flex-1">
            <label>Opening Time *</label>
            <input type="time" v-model="branch.opening_time" required class="form-input" />
          </div>

          <div class="form-group flex-1">
            <label>Closing Time *</label>
            <input type="time" v-model="branch.closing_time" required class="form-input" />
          </div>
        </div>

        <div class="checkbox-box">
          <label class="checkbox-label">
            <input type="checkbox" v-model="branch.is_open" />
            <div>
              <strong>Accept Customer Orders</strong>
              <small>When unchecked, customers will see this store branch as currently closed.</small>
            </div>
          </label>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="isSaving">
          {{ isSaving ? 'Saving...' : 'Save Settings' }}
        </button>
      </form>

      <!-- Geofence Info -->
      <div class="info-box">
        <h3 class="box-title">Geofence & Dispatch</h3>
        <p class="info-desc">
          Customers located within <strong>{{ branch.delivery_radius_km }} km</strong> of this branch can place orders for immediate express delivery.
        </p>

        <div class="geo-meta">
          <div class="geo-item">
            <span>Latitude</span>
            <strong>{{ Number(branch.latitude).toFixed(6) }}</strong>
          </div>
          <div class="geo-item">
            <span>Longitude</span>
            <strong>{{ Number(branch.longitude).toFixed(6) }}</strong>
          </div>
          <div class="geo-item">
            <span>Operating Status</span>
            <strong>{{ branch.is_open ? 'Open & Accepting Orders' : 'Store Paused' }}</strong>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.manager-branch-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-head {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
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

.settings-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1.25rem;
}

.settings-box, .info-box {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 1.25rem;
}

.box-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 1rem;
}

.settings-box {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
}

.form-row {
  display: flex;
  gap: 0.85rem;
}

.flex-1 {
  flex: 1;
}

.checkbox-box {
  padding: 0.5rem 0;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-label strong {
  display: block;
  font-size: 0.825rem;
  color: #111827;
}

.checkbox-label small {
  display: block;
  font-size: 0.72rem;
  color: #6b7280;
}

.info-desc {
  font-size: 0.825rem;
  color: #4b5563;
  line-height: 1.5;
  margin-bottom: 1rem;
}

.geo-meta {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  border-top: 1px solid #e5e7eb;
  padding-top: 0.85rem;
}

.geo-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
}

.geo-item span {
  color: #6b7280;
}

.geo-item strong {
  color: #111827;
}

@media (max-width: 900px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
}
</style>
