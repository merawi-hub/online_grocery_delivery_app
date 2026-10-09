<script setup>
import { defineProps } from 'vue';
import { useCartStore } from '../stores/cart';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const cartStore = useCartStore();

function addToCart() {
  cartStore.addItem(props.product);
}
</script>

<template>
  <div class="product-card">
    <div class="product-image-container">
      <img 
        :src="product.image_url || '/images/placeholder-product.jpg'" 
        :alt="product.name"
        class="product-image"
      />
    </div>
    
    <div class="product-info">
      <h3 class="product-name">{{ product.name }}</h3>
      
      <div class="product-pricing">
        <span v-if="product.discount_price" class="price-original">${{ product.price }}</span>
        <span class="price-current">${{ product.discount_price || product.price }}</span>
      </div>

      <button @click="addToCart" class="btn-add-cart">
        Add to cart
      </button>
    </div>
  </div>
</template>

<style scoped>
.product-card {
  background: #ffffff;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s;
  cursor: pointer;
}

.product-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #D5D9D9;
}

.product-image-container {
  width: 100%;
  aspect-ratio: 1;
  background: #F9FAFB;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  padding: 1rem;
}

.product-name {
  font-size: 0.9375rem;
  font-weight: 500;
  color: #0F1111;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.3;
  min-height: 2.6em;
}

.product-pricing {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.price-original {
  font-size: 0.875rem;
  color: #6B7280;
  text-decoration: line-through;
}

.price-current {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0F1111;
}

.btn-add-cart {
  width: 100%;
  padding: 0.625rem 1rem;
  background: linear-gradient(to bottom, #F7DDBF, #F0C491);
  border: 1px solid #AE7B3E;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0F1111;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-add-cart:hover {
  background: linear-gradient(to bottom, #F5D4A6, #EDB976);
  border-color: #9B6B31;
}

.btn-add-cart:active {
  background: #EDB976;
  transform: scale(0.98);
}
</style>
