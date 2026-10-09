<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useCartStore } from '../stores/cart';
import { useAddressStore } from '../stores/address';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const emit = defineEmits(['open-address-modal']);

const router = useRouter();
const cartStore = useCartStore();
const addressStore = useAddressStore();
const authStore = useAuthStore();

const isSubmitting = ref(false);
const errorMessage = ref('');

const activeAddress = computed(() => addressStore.activeAddress);

async function handleCheckout() {
  errorMessage.value = '';

  if (!authStore.isAuthenticated) {
    router.push('/login?returnUrl=/cart');
    return;
  }

  if (!activeAddress.value) {
    errorMessage.value = 'Please select or add a delivery address.';
    emit('open-address-modal');
    return;
  }

  if (cartStore.items.length === 0) {
    errorMessage.value = 'Your cart is empty.';
    return;
  }

  isSubmitting.value = true;
  try {
    const payload = {
      branch_id: authStore.selectedBranchId,
      address_id: activeAddress.value.id,
      delivery_type: cartStore.deliveryType,
      payment_method: cartStore.paymentMethod,
      special_notes: cartStore.specialNotes,
      items: cartStore.items.map((i) => ({
        product_id: i.product.id,
        quantity: i.quantity,
        substitution_preference: i.substitution_preference,
      })),
    };

    const res = await api.post('/orders', payload);
    const order = res.data.order;

    // Clear cart after successful order
    cartStore.clearCart();

    // Redirect straight to live Yango tracking view!
    router.push(`/tracking/${order.id}`);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to place order. Please try again.';
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="cart-page">
    <div class="app-container">
      <h1 class="page-title">Review Grocery Basket & Fulfillment</h1>

      <div v-if="cartStore.items.length === 0" class="empty-cart-card card">
        <span class="empty-cart-icon">🛒</span>
        <h2>Your basket is empty</h2>
        <p>Explore our fresh produce, bakery, and supermarket essentials.</p>
        <router-link to="/" class="btn btn-primary">Browse Supermarket</router-link>
      </div>

      <div v-else class="cart-layout">
        <!-- LEFT: Items and Substitution Engine -->
        <div class="cart-items-column">
          <div v-if="errorMessage" class="error-banner">{{ errorMessage }}</div>

          <div class="cart-items-card card">
            <div class="card-header">
              <h3>Cart Items ({{ cartStore.items.length }})</h3>
              <button class="clear-btn" @click="cartStore.clearCart">Clear Basket</button>
            </div>

            <div class="items-list">
              <div v-for="item in cartStore.items" :key="item.product.id" class="cart-item">
                <img :src="item.product.image_url" :alt="item.product.name" class="item-img" />

                <div class="item-details">
                  <div class="item-title-row">
                    <h4 class="item-name">{{ item.product.name }}</h4>
                    <span class="item-price">
                      ETB {{ (item.product.price * item.quantity).toFixed(2) }}
                    </span>
                  </div>

                  <div class="item-meta">
                    <span class="rate-note">
                      ETB {{ Number(item.product.price).toFixed(2) }} / {{ item.product.unit_of_measure }}
                    </span>
                    <span v-if="item.product.pricing_type === 'weighted'" class="badge badge-amber">
                      ⚖️ Weighted Item
                    </span>
                  </div>

                  <!-- Substitution Preference Selector -->
                  <div class="substitution-box">
                    <label class="sub-label">If out of stock:</label>
                    <select
                      v-model="item.substitution_preference"
                      class="sub-select"
                      @change="cartStore.save"
                    >
                      <option value="best_match">🔄 Allow best match replacement</option>
                      <option value="contact_customer">📞 Call me before substituting</option>
                      <option value="do_not_substitute">❌ Do not replace (refund item)</option>
                    </select>
                  </div>

                  <!-- Stepper & Remove -->
                  <div class="item-actions">
                    <div class="stepper">
                      <button class="step-btn" @click="cartStore.updateQuantity(item.product.id, -1)">-</button>
                      <span class="step-val">
                        {{ item.product.pricing_type === 'weighted' ? `${item.quantity}kg` : item.quantity }}
                      </span>
                      <button class="step-btn" @click="cartStore.updateQuantity(item.product.id, 1)">+</button>
                    </div>

                    <button class="delete-btn" @click="cartStore.removeItem(item.product.id)">
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Weighted Items Notice -->
          <div class="weighted-notice card">
            <span class="notice-icon">⚖️</span>
            <div>
              <strong>Supermarket Fresh Produce Transparency:</strong>
              <p>
                Fresh fruits, vegetables, and butcher cuts are weighed by your picker.
                Your final receipt will automatically adjust if the scale measurement varies slightly.
              </p>
            </div>
          </div>
        </div>

        <!-- RIGHT: Delivery Address, Slot, Summary -->
        <div class="cart-summary-column">
          <!-- Granular Address Preview -->
          <div class="summary-card card">
            <div class="card-header">
              <h4>📍 Delivery Address (Courier Target)</h4>
              <button class="change-link" @click="emit('open-address-modal')">Change</button>
            </div>

            <div v-if="activeAddress" class="address-preview">
              <span class="badge badge-green">{{ activeAddress.label.toUpperCase() }}</span>
              <p class="addr-primary">
                {{ activeAddress.building_name_or_no || activeAddress.street_name }}, House #{{ activeAddress.house_villa_no }}
              </p>
              <p class="addr-secondary">
                {{ activeAddress.floor_number ? `Floor: ${activeAddress.floor_number}, ` : '' }}
                {{ activeAddress.apartment_unit ? `Apt: ${activeAddress.apartment_unit}` : '' }}
              </p>
              <p class="addr-tertiary">
                {{ activeAddress.street_name }}, {{ activeAddress.district_area }}, {{ activeAddress.city }}
              </p>
              <p v-if="activeAddress.gate_code" class="gate-tag">
                🔑 Gate Code: {{ activeAddress.gate_code }}
              </p>
              <p v-if="activeAddress.nearby_landmark" class="landmark-tag">
                🏛️ {{ activeAddress.nearby_landmark }}
              </p>
            </div>
            <div v-else class="no-address-state">
              <p>No address selected.</p>
              <button class="btn btn-outline btn-sm" @click="emit('open-address-modal')">
                + Set Address
              </button>
            </div>
          </div>

          <!-- Delivery Mode -->
          <div class="summary-card card">
            <h4>🚚 Delivery Speed</h4>
            <div class="radio-group">
              <label class="radio-option" :class="{ selected: cartStore.deliveryType === 'express_immediate' }">
                <input type="radio" value="express_immediate" v-model="cartStore.deliveryType" />
                <div>
                  <strong>⚡ Express Immediate</strong>
                  <p class="radio-desc">Picked & dispatched now (~20–30 mins)</p>
                </div>
              </label>

              <label class="radio-option" :class="{ selected: cartStore.deliveryType === 'scheduled_slot' }">
                <input type="radio" value="scheduled_slot" v-model="cartStore.deliveryType" />
                <div>
                  <strong>📅 Scheduled Slot</strong>
                  <p class="radio-desc">Pick delivery window later today</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="summary-card card">
            <h4>💳 Payment Handover</h4>
            <div class="radio-group">
              <label class="radio-option" :class="{ selected: cartStore.paymentMethod === 'cash_on_delivery' }">
                <input type="radio" value="cash_on_delivery" v-model="cartStore.paymentMethod" />
                <div>
                  <strong>💵 Cash on Delivery</strong>
                  <p class="radio-desc">Pay upon courier arrival after inspection</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Price Totals -->
          <div class="summary-card card totals-card">
            <h4>Order Total</h4>
            <div class="total-row">
              <span>Items Subtotal</span>
              <span>ETB {{ cartStore.subtotal.toFixed(2) }}</span>
            </div>
            <div class="total-row">
              <span>Delivery Fee</span>
              <span>ETB {{ cartStore.deliveryFee.toFixed(2) }}</span>
            </div>
            <div class="total-divider"></div>
            <div class="total-row grand-total">
              <span>Estimated Total</span>
              <span class="grand-price">ETB {{ cartStore.estimatedTotal.toFixed(2) }}</span>
            </div>

            <button
              class="btn btn-primary checkout-btn"
              :disabled="isSubmitting"
              @click="handleCheckout"
            >
              <span v-if="isSubmitting">Transmitting Order...</span>
              <span v-else>Confirm & Track Live Delivery 🚀</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cart-page {
  padding: 0 0 3rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
  color: var(--dark);
}

.empty-cart-card {
  padding: 4rem 1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.empty-cart-icon {
  font-size: 3.5rem;
}

.cart-layout {
  display: grid;
  grid-template-columns: 1.4fr 1fr;
  gap: 1.75rem;
}

.cart-items-card, .summary-card {
  padding: 1.25rem 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 0.75rem;
}

.clear-btn, .change-link {
  font-size: 0.8rem;
  color: var(--primary);
  font-weight: 700;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.cart-item {
  display: flex;
  gap: 1rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px dashed var(--border-color);
}
.cart-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.item-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: var(--radius-md);
  background: #f1f5f9;
}

.item-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.item-title-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 0.5rem;
}

.item-name {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--dark);
}

.item-price {
  font-size: 1rem;
  font-weight: 800;
  color: var(--dark);
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.25rem 0 0.65rem;
}

.rate-note {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.substitution-box {
  background: #f8fafc;
  padding: 0.45rem 0.65rem;
  border-radius: var(--radius-sm);
  margin-bottom: 0.65rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sub-label {
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--text-muted);
  white-space: nowrap;
}

.sub-select {
  flex: 1;
  font-size: 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.25rem 0.5rem;
  background: #ffffff;
}

.item-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
}

.stepper {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--primary-light);
  border: 1px solid #a7f3d0;
  border-radius: var(--radius-md);
  padding: 0.2rem;
}

.step-btn {
  width: 26px;
  height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: var(--radius-sm);
  font-weight: 700;
  color: var(--primary);
}

.step-val {
  font-size: 0.8rem;
  font-weight: 700;
  min-width: 34px;
  text-align: center;
}

.delete-btn {
  font-size: 0.75rem;
  color: #dc2626;
  font-weight: 600;
}

.weighted-notice {
  margin-top: 1.25rem;
  padding: 1rem 1.25rem;
  display: flex;
  gap: 0.75rem;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  font-size: 0.8rem;
  line-height: 1.5;
  color: #92400e;
}

.notice-icon {
  font-size: 1.3rem;
}

/* Right Column */
.cart-summary-column {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.address-preview {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.addr-primary {
  font-weight: 700;
  color: var(--dark);
  font-size: 0.9rem;
  margin-top: 0.35rem;
}

.addr-secondary, .addr-tertiary {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.gate-tag, .landmark-tag {
  font-size: 0.78rem;
  color: #b45309;
  font-weight: 500;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  margin-top: 0.75rem;
}

.radio-option {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 0.95rem;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: var(--transition);
}

.radio-option.selected {
  border-color: var(--primary);
  background: var(--primary-light);
}

.radio-desc {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.totals-card {
  background: #ffffff;
}

.total-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}

.total-divider {
  height: 1px;
  background: var(--border-color);
  margin: 0.85rem 0;
}

.grand-total {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--dark);
}

.grand-price {
  color: var(--primary);
  font-size: 1.25rem;
}

.checkout-btn {
  width: 100%;
  padding: 0.85rem;
  font-size: 1rem;
  margin-top: 1rem;
}

.error-banner {
  background: #fee2e2;
  color: #991b1b;
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

@media (max-width: 900px) {
  .cart-layout {
    grid-template-columns: 1fr;
  }
}
</style>
