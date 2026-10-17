<script setup>
import { ref, computed } from 'vue';
import { useCartStore } from '../stores/cart';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const cartStore = useCartStore();
const justAdded = ref(false);
const imageError = ref(false);

function formatImageUrl(url) {
  if (!url) return '';
  if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
    return url;
  }
  if (url.startsWith('/api/')) {
    return `http://127.0.0.1:8000${url}`;
  }
  if (url.startsWith('/storage/') || url.startsWith('storage/')) {
    const filename = url.split('/').pop();
    return `http://127.0.0.1:8000/api/catalog/product-image/${filename}`;
  }
  return url;
}

const displayImageUrl = computed(() => {
  if (imageError.value) return '';
  return formatImageUrl(props.product.image_url);
});

function handleImageError() {
  imageError.value = true;
}

const categoryIcon = computed(() => {
  const cat = (props.product.category?.name || '').toLowerCase();
  if (cat.includes('fruit') || cat.includes('vegetable')) return '🥦';
  if (cat.includes('dairy') || cat.includes('egg') || cat.includes('milk')) return '🥛';
  if (cat.includes('bakery') || cat.includes('bread') || cat.includes('pastr')) return '🥖';
  if (cat.includes('meat') || cat.includes('poultry') || cat.includes('butcher')) return '🥩';
  if (cat.includes('pantry') || cat.includes('oil') || cat.includes('grain')) return '🌾';
  if (cat.includes('beverage') || cat.includes('coffee') || cat.includes('tea') || cat.includes('drink')) return '☕';
  if (cat.includes('chocolate') || cat.includes('candy') || cat.includes('sweet')) return '🍫';
  if (cat.includes('qollo') || cat.includes('kolo') || cat.includes('nut')) return '🍿';
  if (cat.includes('snack')) return '🍪';
  if (cat.includes('frozen')) return '🧊';
  if (cat.includes('house') || cat.includes('clean')) return '🧼';
  if (cat.includes('care') || cat.includes('beauty')) return '🧴';
  return '🛒';
});

function addToCart() {
  if (!props.product.is_in_stock) return;
  cartStore.addItem(props.product);
  justAdded.value = true;
  setTimeout(() => {
    justAdded.value = false;
  }, 1200);
}
</script>

<template>
  <div class="product-card" :class="{ 'is-out-of-stock': !product.is_in_stock }">
    <div class="product-image-container">
      <img 
        v-if="displayImageUrl"
        :src="displayImageUrl" 
        :alt="product.name"
        class="product-image"
        loading="lazy"
        @error="handleImageError"
      />
      <div v-else class="product-placeholder">
        <span class="placeholder-emoji">{{ categoryIcon }}</span>
        <span class="placeholder-cat">{{ product.category?.name || 'Fresh Grocery' }}</span>
      </div>

      <span v-if="product.is_recent" class="badge-new">🌟 New Arrival</span>
      <span v-if="product.is_perishable" class="badge-perishable" title="Keep Cool">❄️ Cool</span>
      <span v-if="!product.is_in_stock" class="badge-stock-out">Out of Stock</span>
    </div>
    
    <div class="product-info">
      <span v-if="product.category?.name" class="product-category-tag">
        {{ product.category.name }}
      </span>
      <h3 class="product-name" :title="product.name">{{ product.name }}</h3>
      
      <div class="product-pricing">
        <span v-if="product.discount_price" class="price-original">ETB {{ Number(product.price).toFixed(2) }}</span>
        <span class="price-current">ETB {{ Number(product.discount_price || product.price).toFixed(2) }}</span>
        <span class="unit-tag">/ {{ product.unit_of_measure || 'piece' }}</span>
      </div>

      <button 
        @click="addToCart" 
        class="btn-add-cart"
        :class="{ 'added': justAdded, 'disabled': !product.is_in_stock }"
        :disabled="!product.is_in_stock"
      >
        <span v-if="!product.is_in_stock">Sold Out</span>
        <span v-else-if="justAdded">✓ Added to Cart!</span>
        <span v-else>🛒 Add to cart</span>
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
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  display: flex;
  flex-direction: column;
  position: relative;
}

.product-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
  border-color: #cbd5e1;
  transform: translateY(-2px);
}

.product-card.is-out-of-stock {
  opacity: 0.75;
}

.product-image-container {
  width: 100%;
  aspect-ratio: 1;
  background: #F8FAFC;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover .product-image {
  transform: scale(1.04);
}

.product-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 1rem;
  text-align: center;
}

.placeholder-emoji {
  font-size: 3.25rem;
  line-height: 1;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.08));
  transition: transform 0.25s ease;
}

.product-card:hover .placeholder-emoji {
  transform: scale(1.15) rotate(4deg);
}

.placeholder-cat {
  font-size: 0.72rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: #ffffff;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
}

.badge-new {
  position: absolute;
  top: 8px;
  left: 8px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.22rem 0.55rem;
  border-radius: 999px;
  box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
  z-index: 2;
}

.badge-perishable {
  position: absolute;
  top: 8px;
  left: 8px;
  background: rgba(239, 246, 255, 0.9);
  color: #1d4ed8;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
  backdrop-filter: blur(4px);
}

.badge-stock-out {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(239, 68, 68, 0.9);
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
}

.product-info {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.product-category-tag {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin-bottom: 0.25rem;
}

.product-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #0F172A;
  margin-bottom: 0.6rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.35;
  min-height: 2.6em;
}

.product-pricing {
  display: flex;
  align-items: baseline;
  gap: 0.4rem;
  margin-bottom: 0.85rem;
  margin-top: auto;
  flex-wrap: wrap;
}

.price-original {
  font-size: 0.8rem;
  color: #94A3B8;
  text-decoration: line-through;
}

.price-current {
  font-size: 1.15rem;
  font-weight: 700;
  color: #166534;
}

.unit-tag {
  font-size: 0.78rem;
  color: #64748b;
}

.btn-add-cart {
  width: 100%;
  padding: 0.625rem 1rem;
  background: var(--primary, #16a34a);
  color: #ffffff;
  border: 1px solid transparent;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

.btn-add-cart:hover:not(:disabled) {
  background: #15803d;
  box-shadow: 0 4px 10px rgba(22, 163, 74, 0.25);
}

.btn-add-cart.added {
  background: #0f766e !important;
  color: #ffffff !important;
}

.btn-add-cart:disabled,
.btn-add-cart.disabled {
  background: #e2e8f0;
  color: #94a3b8;
  border-color: #cbd5e1;
  cursor: not-allowed;
  box-shadow: none;
}
</style>
