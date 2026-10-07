<script setup>
import { ref, reactive, computed } from 'vue';
import { useAddressStore } from '../stores/address';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const addressStore = useAddressStore();
const isCreatingNew = ref(false);
const errorMsg = ref('');
const isSubmitting = ref(false);

const form = reactive({
  label: 'home',
  latitude: 9.01250000,
  longitude: 38.76980000,
  city: 'Addis Ababa',
  district_area: 'Bole Sub-City',
  street_name: 'Cameroon Street',
  building_name_or_no: 'Mega Tower',
  house_villa_no: 'B-12',
  floor_number: '3rd Floor',
  apartment_unit: 'Unit 304',
  gate_code: '#4512',
  nearby_landmark: 'Next to Friendship Mall, black gate',
  delivery_instructions: 'Ring doorbell twice. Courier can use service elevator.',
  is_default: true,
});

function selectSaved(addr) {
  addressStore.selectAddress(addr.id);
  emit('close');
}

async function handleSaveNew() {
  errorMsg.value = '';
  isSubmitting.value = true;
  const res = await addressStore.saveAddress(form);
  isSubmitting.value = false;

  if (res.success) {
    isCreatingNew.value = false;
    emit('close');
  } else {
    errorMsg.value = res.error;
  }
}

// Preset GPS pins for rapid testing in prototype
function setPreset(lat, lng, district, landmark) {
  form.latitude = lat;
  form.longitude = lng;
  form.district_area = district;
  form.nearby_landmark = landmark;
}
</script>

<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card card">
      <div class="modal-header">
        <div>
          <h2 class="modal-title">Delivery Location & Precision Address</h2>
          <p class="modal-subtitle">
            Exact GPS pin drop and building details ensure your courier never gets lost.
          </p>
        </div>
        <button class="close-btn" @click="emit('close')" aria-label="Close">✕</button>
      </div>

      <!-- Tab Switcher: Saved Addresses vs Add New -->
      <div class="modal-tabs">
        <button
          class="tab-btn"
          :class="{ active: !isCreatingNew }"
          @click="isCreatingNew = false"
        >
          Saved Addresses ({{ addressStore.addresses.length }})
        </button>
        <button
          class="tab-btn"
          :class="{ active: isCreatingNew }"
          @click="isCreatingNew = true"
        >
          + Add New Granular Address
        </button>
      </div>

      <!-- TAB 1: SAVED ADDRESSES -->
      <div v-if="!isCreatingNew" class="saved-list-container">
        <div v-if="addressStore.addresses.length === 0" class="empty-state">
          <p>No saved addresses yet.</p>
          <button class="btn btn-primary btn-sm" @click="isCreatingNew = true">
            Add Your First Address
          </button>
        </div>

        <div
          v-for="addr in addressStore.addresses"
          :key="addr.id"
          class="address-item"
          :class="{ selected: addressStore.selectedAddressId === addr.id }"
          @click="selectSaved(addr)"
        >
          <div class="address-item-header">
            <span class="badge badge-green">{{ addr.label.toUpperCase() }}</span>
            <span v-if="addr.is_default" class="default-tag">Default</span>
            <span class="coords-pill">📍 {{ addr.latitude.toFixed(4) }}, {{ addr.longitude.toFixed(4) }}</span>
          </div>

          <div class="address-item-body">
            <strong>{{ addr.building_name_or_no || addr.street_name }}, House/Villa #{{ addr.house_villa_no }}</strong>
            <p v-if="addr.floor_number || addr.apartment_unit" class="text-sm">
              Floor {{ addr.floor_number || 'N/A' }}, Apt/Unit {{ addr.apartment_unit || 'N/A' }}
            </p>
            <p class="text-sm text-muted">
              {{ addr.street_name }}, {{ addr.district_area }}, {{ addr.city }}
            </p>
            <p v-if="addr.nearby_landmark" class="landmark-line">
              🏛️ Landmark: <em>{{ addr.nearby_landmark }}</em>
            </p>
          </div>
        </div>
      </div>

      <!-- TAB 2: GRANULAR FORM + LIVE GPS PIN -->
      <form v-else class="address-form" @submit.prevent="handleSaveNew">
        <div v-if="errorMsg" class="error-banner">{{ errorMsg }}</div>

        <!-- Simulated Map Pin Radar Card -->
        <div class="gps-radar-box">
          <div class="gps-pin-header">
            <span>🗺️ Live GPS Coordinates (Google Map Anchor)</span>
            <span class="gps-badge">High Precision</span>
          </div>
          <div class="gps-inputs-row">
            <div class="input-col">
              <label>Latitude</label>
              <input type="number" step="0.00000001" v-model.number="form.latitude" required />
            </div>
            <div class="input-col">
              <label>Longitude</label>
              <input type="number" step="0.00000001" v-model.number="form.longitude" required />
            </div>
          </div>
          <!-- Quick Pin Presets -->
          <div class="presets-row">
            <span class="presets-label">Quick Locations:</span>
            <button type="button" class="preset-btn" @click="setPreset(9.0125, 38.7698, 'Kazanchis', 'Behind Radisson Blu Hotel')">
              Kazanchis
            </button>
            <button type="button" class="preset-btn" @click="setPreset(9.0016, 38.7845, 'Bole Medhanialem', 'Near Edna Mall')">
              Bole
            </button>
            <button type="button" class="preset-btn" @click="setPreset(9.0345, 38.7420, 'Piazza / Churchill', 'Beside National Theatre')">
              Piazza
            </button>
          </div>
        </div>

        <!-- Granular Address Fields Grid -->
        <div class="fields-grid">
          <div class="form-group">
            <label>Address Label</label>
            <select v-model="form.label">
              <option value="home">Home 🏠</option>
              <option value="work">Work 🏢</option>
              <option value="other">Other 📍</option>
            </select>
          </div>

          <div class="form-group">
            <label>City</label>
            <input type="text" v-model="form.city" required placeholder="e.g. Addis Ababa" />
          </div>

          <div class="form-group">
            <label>District / Sub-City</label>
            <input type="text" v-model="form.district_area" required placeholder="e.g. Bole Sub-City" />
          </div>

          <div class="form-group">
            <label>Street Name</label>
            <input type="text" v-model="form.street_name" required placeholder="e.g. Cameroon Street" />
          </div>

          <div class="form-group">
            <label>Building Name or #</label>
            <input type="text" v-model="form.building_name_or_no" placeholder="e.g. Sunrise Plaza" />
          </div>

          <div class="form-group">
            <label>House / Villa # *</label>
            <input type="text" v-model="form.house_villa_no" required placeholder="e.g. 14 / B-20" />
          </div>

          <div class="form-group">
            <label>Floor Number</label>
            <input type="text" v-model="form.floor_number" placeholder="e.g. 3rd Floor" />
          </div>

          <div class="form-group">
            <label>Apartment / Unit #</label>
            <input type="text" v-model="form.apartment_unit" placeholder="e.g. Apt 302" />
          </div>

          <div class="form-group">
            <label>Gate / Intercom Code</label>
            <input type="text" v-model="form.gate_code" placeholder="e.g. #9921" />
          </div>

          <div class="form-group full-width">
            <label>Nearby Landmark (For Couriers)</label>
            <input type="text" v-model="form.nearby_landmark" placeholder="e.g. Behind the green pharmacy, yellow gate" />
          </div>

          <div class="form-group full-width">
            <label>Delivery Instructions</label>
            <textarea v-model="form.delivery_instructions" rows="2" placeholder="e.g. Please ring the doorbell. Elevator is active."></textarea>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-outline" @click="isCreatingNew = false">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Saving...' : 'Save & Select Address' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-card {
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: var(--radius-lg);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--dark);
}

.modal-subtitle {
  font-size: 0.8rem;
  color: var(--text-muted);
  margin-top: 0.2rem;
}

.close-btn {
  font-size: 1.2rem;
  color: var(--text-light);
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
}
.close-btn:hover {
  background: #f1f5f9;
  color: var(--dark);
}

.modal-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  background: #f8fafc;
}

.tab-btn {
  flex: 1;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-muted);
  border-bottom: 2px solid transparent;
}

.tab-btn.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
  background: #ffffff;
}

.saved-list-container {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.address-item {
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 0.9rem 1.1rem;
  cursor: pointer;
  transition: var(--transition);
}

.address-item:hover, .address-item.selected {
  border-color: var(--primary);
  background: var(--primary-light);
}

.address-item-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.default-tag {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--primary);
}

.coords-pill {
  font-size: 0.7rem;
  color: var(--text-muted);
  background: #ffffff;
  padding: 0.15rem 0.45rem;
  border-radius: var(--radius-full);
  margin-left: auto;
  border: 1px solid var(--border-color);
}

.landmark-line {
  font-size: 0.78rem;
  color: #b45309;
  margin-top: 0.35rem;
}

.address-form {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
}

.gps-radar-box {
  background: #f0fdf4;
  border: 1px dashed #10b981;
  border-radius: var(--radius-md);
  padding: 0.85rem 1rem;
  margin-bottom: 1.25rem;
}

.gps-pin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  font-weight: 700;
  color: #065f46;
  margin-bottom: 0.65rem;
}

.gps-badge {
  font-size: 0.68rem;
  background: #059669;
  color: #ffffff;
  padding: 0.15rem 0.4rem;
  border-radius: var(--radius-full);
}

.gps-inputs-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.presets-row {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.6rem;
  flex-wrap: wrap;
}

.presets-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #047857;
}

.preset-btn {
  font-size: 0.7rem;
  background: #ffffff;
  border: 1px solid #a7f3d0;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-full);
  font-weight: 600;
  color: #065f46;
}
.preset-btn:hover {
  background: #059669;
  color: #ffffff;
}

.fields-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.form-group.full-width {
  grid-column: span 2;
}

.form-group label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--dark);
}

.form-group input, .form-group select, .form-group textarea {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.55rem 0.75rem;
  font-size: 0.85rem;
  background: #ffffff;
  transition: var(--transition);
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-glow);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.65rem;
  border-radius: var(--radius-sm);
  font-size: 0.825rem;
  margin-bottom: 0.85rem;
}

.text-sm {
  font-size: 0.825rem;
}
</style>
