<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../services/api';
import ProductCard from '../components/ProductCard.vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();

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
    const params = {
      branch_id: authStore.selectedBranchId,
    };
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
  <div class="storefront-page">
    <!-- Clean Search & Filter Bar -->
    <div class="storefront-top card">
      <div class="search-input-wrapper">
        <span class="search-ico">🔍</span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search fresh bananas, whole milk, chicken breast, coffee..."
        />
        <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''">✕</button>
      </div>

      <!-- Compact Category Aisle Pills -->
      <div class="category-pills">
        <button
          class="pill-btn"
          :class="{ active: selectedCategory === '' }"
          @click="handleCategorySelect('')"
        >
          All Aisles ({{ products.length }})
        </button>

        <button
          v-for="cat in categories"
          :key="cat.id"
          class="pill-btn"
          :class="{ active: selectedCategory === cat.slug }"
          @click="handleCategorySelect(cat.slug)"
        >
          <span>{{ cat.icon_url }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>
    </div>

    <!-- Product Grid -->
    <div class="products-container">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading fresh supermarket inventory...</p>
      </div>

      <div v-else-if="products.length === 0" class="empty-state card">
        <span class="empty-icon">🥦</span>
        <h3>No groceries match your search</h3>
        <p>Try searching another item or select a different category aisle.</p>
        <button class="btn btn-outline" @click="selectedCategory = ''; searchQuery = '';">
          Clear Filters
        </button>
      </div>

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
.storefront-page {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.storefront-top {
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: #ffffff;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 0.65rem 1rem;
  transition: var(--transition);
}

.search-input-wrapper:focus-within {
  border-color: var(--primary);
  background: #ffffff;
  box-shadow: 0 0 0 3px var(--primary-glow);
}

.search-ico {
  font-size: 1.1rem;
  margin-right: 0.75rem;
  color: var(--text-light);
}

.search-input-wrapper input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.95rem;
  color: var(--dark);
}

.clear-btn {
  color: var(--text-light);
  font-size: 0.85rem;
}

.category-pills {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.25rem;
}

.pill-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.95rem;
  border-radius: var(--radius-full);
  border: 1px solid var(--border-color);
  background: #ffffff;
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--text-main);
  white-space: nowrap;
  transition: var(--transition);
}

.pill-btn:hover {
  border-color: var(--primary);
  background: var(--primary-light);
}

.pill-btn.active {
  background: var(--primary);
  color: #ffffff;
  border-color: var(--primary);
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.25rem;
}

.loading-state, .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
  gap: 0.75rem;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid var(--primary-light);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-icon {
  font-size: 2.5rem;
}
</style>
