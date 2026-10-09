<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../services/api';
import ProductCard from '../components/ProductCard.vue';
import { useCartStore } from '../stores/cart';

const cartStore = useCartStore();

const categories = ref([]);
const products = ref([]);
const selectedCategory = ref('');
const searchQuery = ref('');
const loading = ref(true);

async function loadCategories() {
  try {
    const res = await api.get('/catalog/categories');
    categories.value = res.data;
  } catch (err) {
    console.error('Failed to load categories', err);
  }
}

async function loadProducts() {
  loading.value = true;
  try {
    const params = {};
    if (selectedCategory.value) {
      params.category = selectedCategory.value;
    }
    if (searchQuery.value) {
      params.search = searchQuery.value;
    }
    const res = await api.get('/catalog/products', { params });
    products.value = res.data;
  } catch (err) {
    console.error('Failed to load products', err);
  } finally {
    loading.value = false;
  }
}

function handleCategorySelect(slug) {
  selectedCategory.value = selectedCategory.value === slug ? '' : slug;
  loadProducts();
}

let searchDebounce = null;
watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    loadProducts();
  }, 300);
});

onMounted(() => {
  loadCategories();
  loadProducts();
});
</script>

<template>
  <div class="home-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Fresh groceries delivered to your door</h1>
        <p class="hero-subtitle">Shop from thousands of products • Get delivery in 30 minutes</p>
      </div>
    </div>

    <!-- Categories -->
    <div class="categories-section">
      <h2 class="section-title">Shop by category</h2>
      <div class="category-grid">
        <button
          v-for="cat in categories"
          :key="cat.id"
          class="category-card"
          :class="{ active: selectedCategory === cat.slug }"
          @click="handleCategorySelect(cat.slug)"
        >
          <div class="category-icon">{{ cat.icon_url || '🛒' }}</div>
          <span class="category-name">{{ cat.name }}</span>
        </button>
      </div>
    </div>

    <!-- Products -->
    <div class="products-section">
      <div class="section-header">
        <h2 class="section-title">
          {{ selectedCategory ? 'Products in ' + selectedCategory : 'All products' }}
        </h2>
        <span class="result-count">{{ products.length }} items</span>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p class="loading-text">Loading products...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="products.length === 0" class="empty-container">
        <div class="empty-icon">🔍</div>
        <h3 class="empty-title">No products found</h3>
        <p class="empty-text">Try adjusting your search or filters</p>
        <button class="btn-secondary" @click="selectedCategory = ''; searchQuery = '';">
          Clear all filters
        </button>
      </div>

      <!-- Product Grid -->
      <div v-else class="product-grid">
        <ProductCard
          v-for="prod in products"
          :key="prod.id"
          :product="prod"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.home-page {
  min-height: 100vh;
  background: #fafaf9;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 3rem 1.5rem;
  text-align: center;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #0F1111;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.hero-subtitle {
  font-size: 1.125rem;
  color: #565959;
  font-weight: 400;
}

/* Categories Section */
.categories-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0F1111;
  margin-bottom: 1.25rem;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 1rem;
}

.category-card {
  background: #ffffff;
  border: 1px solid #E5E7EB;
  border-radius: 12px;
  padding: 1.25rem 1rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.category-card:hover {
  border-color: #D5D9D9;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.category-card.active {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.category-icon {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.category-name {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0F1111;
}

/* Products Section */
.products-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.result-count {
  font-size: 0.875rem;
  color: #6B7280;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.25rem;
}

/* Loading State */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #E5E7EB;
  border-top-color: #067D62;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  margin-top: 1rem;
  color: #6B7280;
  font-size: 0.875rem;
}

/* Empty State */
.empty-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #0F1111;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #6B7280;
  margin-bottom: 1.5rem;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: #ffffff;
  border: 1px solid #D5D9D9;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0F1111;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  border-color: #A2A6AC;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

/* Responsive */
@media (max-width: 768px) {
  .hero-title {
    font-size: 1.75rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .category-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
  }

  .category-card {
    padding: 1rem 0.75rem;
  }

  .category-icon {
    font-size: 2rem;
  }

  .product-grid {
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
  }
}
</style>
