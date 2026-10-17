<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import api from '../services/api';
import ProductCard from '../components/ProductCard.vue';
import { useCartStore } from '../stores/cart';
import { useAuthStore } from '../stores/auth';

const route = useRoute();
const cartStore = useCartStore();
const authStore = useAuthStore();

const categories = ref([]);
const products = ref([]);
const selectedCategory = ref('');
const searchQuery = ref('');
const filterMode = ref('all'); // 'all' or 'new_arrivals'
const loading = ref(true);
const isRefreshing = ref(false);
let catalogChannel = null;

async function loadCategories() {
  try {
    const res = await api.get('/catalog/categories');
    categories.value = res.data;
  } catch (err) {
    console.error('Failed to load categories', err);
  }
}

async function loadProducts(showSpinner = false) {
  if (showSpinner || products.value.length === 0) {
    loading.value = true;
  }
  isRefreshing.value = true;
  try {
    const params = {};
    if (authStore.selectedBranchId) {
      params.branch_id = authStore.selectedBranchId;
    }
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
    setTimeout(() => { isRefreshing.value = false; }, 300);
  }
}

function handleCategorySelect(slug) {
  selectedCategory.value = selectedCategory.value === slug ? '' : slug;
  loadProducts();
}

const activeCategoryName = computed(() => {
  if (!selectedCategory.value) return 'All Products';
  const found = categories.value.find(c => c.slug === selectedCategory.value);
  return found ? found.name : selectedCategory.value;
});

const newProductsCount = computed(() => {
  return products.value.filter(p => p.is_recent).length;
});

const displayedProducts = computed(() => {
  if (filterMode.value === 'new_arrivals') {
    return products.value.filter(p => p.is_recent);
  }
  return products.value;
});

let searchDebounce = null;
watch(searchQuery, () => {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    loadProducts();
  }, 300);
});

// Watch route query changes (e.g. from header search or links)
watch(() => route.query, (newQuery) => {
  if (newQuery.search !== undefined) {
    searchQuery.value = newQuery.search;
  }
  if (newQuery.category !== undefined) {
    selectedCategory.value = newQuery.category;
  }
  if (newQuery.new === 'true') {
    filterMode.value = 'new_arrivals';
  }
  loadProducts();
}, { immediate: true });

function onWindowFocus() {
  loadProducts(false);
  loadCategories();
}

function handleStorageUpdate(e) {
  if (e.key === 'grocery_catalog_last_updated') {
    loadCategories();
    loadProducts(false);
  }
}

function handleVisibilityChange() {
  if (!document.hidden) {
    loadCategories();
    loadProducts(false);
  }
}

let pollTimer = null;

onMounted(() => {
  loadCategories();
  loadProducts(true);

  // 1. Real-time BroadcastChannel across browser tabs/windows
  try {
    catalogChannel = new BroadcastChannel('grocery_catalog_updates');
    catalogChannel.onmessage = () => {
      loadCategories();
      loadProducts(false);
    };
  } catch (_) {}

  // 2. Cross-tab storage event
  window.addEventListener('storage', handleStorageUpdate);

  // 3. Tab visibility change & focus
  window.addEventListener('focus', onWindowFocus);
  document.addEventListener('visibilitychange', handleVisibilityChange);

  // 4. Smooth 4-second background poll for real-time live sync
  pollTimer = setInterval(() => {
    loadCategories();
    loadProducts(false);
  }, 4000);
});

onUnmounted(() => {
  if (catalogChannel) {
    catalogChannel.close();
  }
  if (pollTimer) {
    clearInterval(pollTimer);
  }
  window.removeEventListener('storage', handleStorageUpdate);
  window.removeEventListener('focus', onWindowFocus);
  document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
  <div class="home-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Fresh groceries delivered to your door</h1>
        <p class="hero-subtitle">Shop from verified stock • 30 minute delivery in Addis Ababa</p>
      </div>
    </div>

    <!-- Categories -->
    <div class="categories-section">
      <div class="cat-head-row">
        <h2 class="section-title">Shop by category</h2>
        <span class="db-pill">🟢 Live Catalog from Database</span>
      </div>
      <div class="category-grid">
        <button
          class="category-card"
          :class="{ active: selectedCategory === '' }"
          @click="handleCategorySelect('')"
        >
          <div class="category-icon">🧺</div>
          <span class="category-name">All Items</span>
        </button>
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
        <div>
          <h2 class="section-title">
            {{ activeCategoryName }}
          </h2>
          <div class="filter-tabs">
            <button
              class="tab-btn"
              :class="{ active: filterMode === 'all' }"
              @click="filterMode = 'all'"
            >
              All Items ({{ products.length }})
            </button>
            <button
              class="tab-btn new-tab"
              :class="{ active: filterMode === 'new_arrivals' }"
              @click="filterMode = 'new_arrivals'"
            >
              🌟 New Arrivals ({{ newProductsCount }})
            </button>
          </div>
        </div>

        <div class="header-tools">
          <button
            class="btn-refresh"
            @click="loadProducts"
            :disabled="isRefreshing"
            title="Fetch latest stock directly from database"
          >
            <span :class="{ spinning: isRefreshing }">🔄</span>
            <span>{{ isRefreshing ? 'Updating...' : 'Refresh Catalog' }}</span>
          </button>
          <span class="result-count">{{ displayedProducts.length }} items</span>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p class="loading-text">Fetching latest products from database...</p>
      </div>

      <!-- Empty State: True Store Empty (No products added by manager yet) -->
      <div v-else-if="products.length === 0 && !searchQuery && !selectedCategory" class="empty-container store-empty">
        <div class="empty-icon">🛒</div>
        <h3 class="empty-title">No products available yet</h3>
        <p class="empty-text">
          All default dummy products have been removed. Products will appear here immediately as soon as a store manager registers them.
        </p>
        <div class="empty-actions">
          <button class="btn-refresh-primary" @click="loadProducts" :disabled="isRefreshing">
            <span>🔄</span> Refresh Store Catalog
          </button>
          <router-link to="/admin123/inventory" class="btn-manager-cta">
            <span>➕</span> Open Store Manager Portal to Add Products ↗
          </router-link>
        </div>
      </div>

      <!-- Empty State: Filter mismatch -->
      <div v-else-if="displayedProducts.length === 0" class="empty-container">
        <div class="empty-icon">🔍</div>
        <h3 class="empty-title">No products found</h3>
        <p class="empty-text">
          {{ selectedCategory ? `No products found in "${activeCategoryName}".` : 'No products matching your search.' }}
        </p>
        <button class="btn-secondary" @click="selectedCategory = ''; searchQuery = ''; filterMode = 'all'; loadProducts();">
          Show All Store Products
        </button>
      </div>

      <!-- Product Grid -->
      <div v-else class="product-grid">
        <ProductCard
          v-for="prod in displayedProducts"
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

.cat-head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.db-pill {
  font-size: 0.75rem;
  font-weight: 600;
  color: #059669;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filter-tabs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.tab-btn {
  padding: 0.35rem 0.75rem;
  font-size: 0.82rem;
  font-weight: 600;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #475569;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tab-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.tab-btn.active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}

.tab-btn.new-tab.active {
  background: #059669;
  border-color: #059669;
  color: #ffffff;
}

.header-tools {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #334155;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-refresh:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

.result-count {
  font-size: 0.875rem;
  color: #6B7280;
  font-weight: 500;
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
  max-width: 480px;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-refresh-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #007185;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh-primary:hover {
  background: #005a6a;
  transform: translateY(-1px);
}

.btn-manager-cta {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: #111827;
  color: #ffffff;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.btn-manager-cta:hover {
  background: #1f2937;
  color: #ffffff;
  transform: translateY(-1px);
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
