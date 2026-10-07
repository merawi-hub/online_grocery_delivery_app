<script setup>
import { computed } from 'vue';
import { useCartStore } from '../stores/cart';

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
});

const cartStore = useCartStore();

const qtyInCart = computed(() => {
  return cartStore.getItemQuantity(props.product.id);
});

const isWeighted = computed(() => {
  return props.product.pricing_type === 'weighted';
});

const stepLabel = computed(() => {
  if (isWeighted.value) {
    const grams = props.product.step_weight_grams || 500;
    return `${grams}g steps`;
  }
  return `per ${props.product.unit_of_measure}`;
});

function handleAdd() {
  cartStore.addItem(props.product);
}

function handleIncrement() {
  cartStore.updateQuantity(props.product.id, 1);
}

function handleDecrement() {
  cartStore.updateQuantity(props.product.id, -1);
}
</script>

<template>
  <div class="product-card card">
    <!-- Image & Badges -->
    <div class="product-image-container">
      <img
        :src="product.image_url"
        :alt="product.name"
        class="product-image"
        loading="lazy"
      />
      <div class="badge-overlay">
        <span v-if="product.is_perishable" class="badge badge-green">
          🌱 Fresh
        </span>
        <span class="badge" :class="isWeighted ? 'badge-amber' : 'badge-dark'">
          {{ isWeighted ? '⚖️ Weighted' : '📦 Pack' }}
        </span>
      </div>
    </div>

    <!-- Details -->
    <div class="product-info">
      <div class="product-header">
        <span class="category-name">{{ product.category?.name || 'Groceries' }}</span>
        <span class="unit-step">{{ stepLabel }}</span>
      </div>

      <h3 class="product-title" :title="product.name">
        {{ product.name }}
      </h3>

      <div class="product-footer">
        <div class="price-box">
          <span class="currency">ETB</span>
          <span class="price-value">{{ Number(product.price).toFixed(2) }}</span>
          <span class="price-unit">/ {{ product.unit_of_measure }}</span>
        </div>

        <!-- Add / Stepper Button -->
        <div class="action-box">
          <button
            v-if="qtyInCart === 0"
            class="btn btn-primary add-btn"
            @click="handleAdd"
          >
            <span>+</span> Add
          </button>

          <div v-else class="stepper">
            <button class="step-btn" @click="handleDecrement" aria-label="Decrease">
              -
            </button>
            <span class="step-count">
              {{ isWeighted ? `${qtyInCart}kg` : qtyInCart }}
            </span>
            <button class="step-btn" @click="handleIncrement" aria-label="Increase">
              +
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.product-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #ffffff;
  border-radius: var(--radius-lg);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}

.product-image-container {
  position: relative;
  width: 100%;
  padding-top: 70%; /* 4:3 aspect ratio */
  overflow: hidden;
  background: #f1f5f9;
}

.product-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.04);
}

.badge-overlay {
  position: absolute;
  top: 10px;
  left: 10px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.product-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 1rem 1.15rem 1.15rem;
}

.product-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.4rem;
}

.category-name {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--primary);
  letter-spacing: 0.03em;
}

.unit-step {
  font-size: 0.72rem;
  color: var(--text-light);
  font-weight: 500;
}

.product-title {
  font-size: 0.975rem;
  font-weight: 700;
  color: var(--dark);
  line-height: 1.35;
  margin-bottom: 0.85rem;
  min-height: 2.7em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-footer {
  margin-top: auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  padding-top: 0.65rem;
  border-top: 1px dashed #f1f5f9;
}

.price-box {
  display: flex;
  align-items: baseline;
  gap: 0.2rem;
}

.currency {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
}

.price-value {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--dark);
}

.price-unit {
  font-size: 0.72rem;
  color: var(--text-muted);
  font-weight: 500;
}

.add-btn {
  padding: 0.45rem 0.95rem;
  font-size: 0.85rem;
  border-radius: var(--radius-md);
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
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border-radius: var(--radius-sm);
  font-weight: 700;
  color: var(--primary);
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.step-btn:hover {
  background: var(--primary);
  color: #ffffff;
}

.step-count {
  font-size: 0.825rem;
  font-weight: 700;
  color: var(--dark);
  padding: 0 0.25rem;
  min-width: 32px;
  text-align: center;
}
</style>
