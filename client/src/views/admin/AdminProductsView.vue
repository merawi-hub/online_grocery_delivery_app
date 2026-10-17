<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';

const products = ref([]);
const categories = ref([]);
const isLoading = ref(true);
const actionMessage = ref('');
const errorMessage = ref('');

// Search & Filters
const searchQuery = ref('');
const categoryFilter = ref('all');

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteConfirmModal = ref(false);
const productToDelete = ref(null);
const isSubmitting = ref(false);
const modalError = ref('');
const isSuccessModalOpen = ref(false);
const newlyCreatedProduct = ref(null);
const imageInput = ref(null);

const quickImagePresets = [
  { label: '🍎 Fresh Apples', url: 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80', name: 'Fresh Gala Apples', unit: 'kg', price: 85.00 },
  { label: '🥛 Fresh Milk', url: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=600&q=80', name: 'Pasteurized Whole Milk', unit: 'liter', price: 75.00 },
  { label: '🍞 Sourdough Bread', url: 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=600&q=80', name: 'Artisan Sourdough Loaf', unit: 'piece', price: 60.00 },
  { label: '🥩 Prime Beef', url: 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?auto=format&fit=crop&w=600&q=80', name: 'Prime Beef Steak Cut', unit: 'kg', price: 420.00 },
  { label: '🍅 Vine Tomatoes', url: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=600&q=80', name: 'Ripe Vine Tomatoes', unit: 'kg', price: 55.00 },
  { label: '🍌 Sweet Bananas', url: 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80', name: 'Sweet Highland Bananas', unit: 'kg', price: 45.00 },
];

function compressImage(file, callback) {
  if (!file) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const img = new Image();
    img.onload = () => {
      const maxDim = 800;
      let width = img.width;
      let height = img.height;
      if (width > maxDim || height > maxDim) {
        if (width > height) {
          height = Math.round((height * maxDim) / width);
          width = maxDim;
        } else {
          width = Math.round((width * maxDim) / height);
          height = maxDim;
        }
      }
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, width, height);
      callback(canvas.toDataURL('image/jpeg', 0.82));
    };
    img.onerror = () => callback(e.target.result);
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

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

function handleImageUpload(event) {
  const file = event.target.files[0];
  if (file) {
    compressImage(file, (dataUrl) => {
      productForm.value.image_url = dataUrl;
    });
  }
}

function selectPresetImage(preset) {
  productForm.value.image_url = preset.url;
  if (!productForm.value.name) {
    productForm.value.name = preset.name;
    productForm.value.unit = preset.unit;
    productForm.value.base_price = preset.price;
  }
}

// Product Form
const productForm = ref({
  id: null,
  name: '',
  description: '',
  image_url: '',
  category_id: null,
  base_price: 0,
  unit: 'kg',
  barcode: '',
  is_active: true,
});

async function loadData() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const [productsRes, categoriesRes] = await Promise.all([
      api.get('/admin/products'),
      api.get('/admin/categories'),
    ]);

    products.value = productsRes.data.products;
    categories.value = categoriesRes.data.categories;
  } catch (err) {
    console.error('Failed to load products:', err);
    errorMessage.value = 'Could not load products from server.';
  } finally {
    isLoading.value = false;
  }
}

const filteredProducts = computed(() => {
  return products.value.filter((p) => {
    const matchSearch =
      (p.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (p.barcode || '').includes(searchQuery.value);
    const matchCategory =
      categoryFilter.value === 'all' || p.category_id == categoryFilter.value;
    return matchSearch && matchCategory;
  });
});

function openCreateModal() {
  resetForm();
  modalError.value = '';
  if (categories.value.length > 0 && !productForm.value.category_id) {
    productForm.value.category_id = categories.value[0].id;
  }
  showCreateModal.value = true;
}

function openEditModal(product) {
  modalError.value = '';
  productForm.value = {
    id: product.id,
    name: product.name,
    description: product.description || '',
    image_url: product.image_url || '',
    category_id: product.category_id,
    base_price: product.base_price,
    unit: product.unit,
    barcode: product.barcode || '',
    is_active: product.is_active,
  };
  showEditModal.value = true;
}

function broadcastCatalogUpdate(type) {
  try {
    const channel = new BroadcastChannel('grocery_catalog_updates');
    channel.postMessage({ type, timestamp: Date.now() });
    setTimeout(() => { channel.close(); }, 1500);
  } catch (_) {}
  try {
    localStorage.setItem('grocery_catalog_last_updated', Date.now().toString());
  } catch (_) {}
}

async function handleCreateProduct() {
  modalError.value = '';
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    if (!productForm.value.name || !productForm.value.name.trim()) {
      modalError.value = 'Product Name is required.';
      isSubmitting.value = false;
      return;
    }
    if (!productForm.value.category_id) {
      modalError.value = 'Please select a Category.';
      isSubmitting.value = false;
      return;
    }

    const priceVal = Number(productForm.value.base_price);
    if (!priceVal || isNaN(priceVal) || priceVal <= 0) {
      modalError.value = 'Please enter a valid Base Price (greater than 0 ETB).';
      isSubmitting.value = false;
      return;
    }

    const payload = {
      name: productForm.value.name.trim(),
      description: productForm.value.description || null,
      category_id: productForm.value.category_id,
      base_price: priceVal,
      price: priceVal,
      unit: productForm.value.unit || 'piece',
      unit_of_measure: productForm.value.unit || 'piece',
      barcode: productForm.value.barcode ? productForm.value.barcode.trim() : null,
      image_url: productForm.value.image_url || null,
      is_active: productForm.value.is_active !== false,
    };

    const res = await api.post('/admin/products', payload);
    const created = res.data.product || { ...payload };
    newlyCreatedProduct.value = created;
    actionMessage.value = `✓ Product "${created.name}" created successfully and registered to database!`;
    showCreateModal.value = false;
    isSuccessModalOpen.value = true;
    resetForm();
    await loadData();
    broadcastCatalogUpdate('product_created');
    setTimeout(() => (actionMessage.value = ''), 5000);
  } catch (err) {
    console.error('Failed to create product:', err);
    let msg = 'Failed to create product.';
    if (!err.response) {
      msg = 'Cannot connect to backend server. Please verify backend is running on port 8000.';
    } else if (err.response.status === 413) {
      msg = 'The uploaded image or payload is too large. Please select a smaller photo or use a web link.';
    } else if (err.response.status === 422) {
      msg = err.response.data?.errors ? Object.values(err.response.data.errors).flat().join(' • ') : (err.response.data?.message || 'Invalid product details.');
    } else if (err.response.data?.message) {
      msg = err.response.data.message;
    } else {
      msg = `Server error (${err.response.status}). Please check backend status.`;
    }
    modalError.value = msg;
    errorMessage.value = msg;
  } finally {
    isSubmitting.value = false;
  }
}

async function handleUpdateProduct() {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    await api.put(`/admin/products/${productForm.value.id}`, productForm.value);
    actionMessage.value = 'Product updated successfully!';
    showEditModal.value = false;
    resetForm();
    await loadData();
    broadcastCatalogUpdate('product_updated');
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update product.';
  } finally {
    isSubmitting.value = false;
  }
}

function confirmDelete(product) {
  productToDelete.value = product;
  showDeleteConfirmModal.value = true;
}

async function executeDelete() {
  if (!productToDelete.value) return;
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    await api.delete(`/admin/products/${productToDelete.value.id}`);
    actionMessage.value = `Product '${productToDelete.value.name}' deleted successfully.`;
    showDeleteConfirmModal.value = false;
    productToDelete.value = null;
    await loadData();
    broadcastCatalogUpdate('product_deleted');
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete product.';
  } finally {
    isSubmitting.value = false;
  }
}

function resetForm() {
  productForm.value = {
    id: null,
    name: '',
    description: '',
    image_url: '',
    category_id: null,
    base_price: 50,
    unit: 'piece',
    barcode: '',
    is_active: true,
  };
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <section class="tab-panel">
    <div class="panel-header">
      <div>
        <h2>Product Catalog</h2>
        <p>Create and manage products that will be available across all stores</p>
      </div>

      <div class="header-action-group">
        <router-link to="/products" target="_blank" class="btn btn-customer-link">
          <span>🛒</span> Customer Storefront ↗
        </router-link>
        <button class="btn btn-primary" @click="openCreateModal">
          + Create New Product
        </button>
      </div>
    </div>

    <!-- Success / Error Banner -->
    <div v-if="actionMessage" class="banner success-banner">
      <span>{{ actionMessage }}</span>
      <router-link to="/products" target="_blank" class="banner-link">
        🛒 View on Customer Products Page ↗
      </router-link>
    </div>
    <div v-if="errorMessage" class="banner error-banner">
      ⚠ {{ errorMessage }}
    </div>

    <!-- Search & Filters -->
    <div class="filter-bar">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search products by name or barcode..."
          class="search-input"
        />
      </div>

      <div class="filter-group">
        <label>Category:</label>
        <select v-model="categoryFilter" class="filter-select">
          <option value="all">All Categories</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Products Table -->
    <div class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Base Price</th>
            <th>Unit</th>
            <th>Barcode</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="7" class="text-center py-6 text-muted">Loading products...</td>
          </tr>
          <tr v-else-if="filteredProducts.length === 0">
            <td colspan="7" class="text-center py-6 text-muted">
              No products found matching your criteria.
            </td>
          </tr>
          <tr v-for="product in filteredProducts" :key="product.id">
            <td>
              <div class="product-cell">
                <img
                  v-if="product.image_url"
                  :src="formatImageUrl(product.image_url)"
                  :alt="product.name"
                  class="product-img"
                />
                <div v-else class="product-img-placeholder">📦</div>
                <div>
                  <strong class="product-name">{{ product.name }}</strong>
                  <small class="product-desc" v-if="product.description">
                    {{ product.description }}
                  </small>
                </div>
              </div>
            </td>
            <td>
              <span class="category-badge">{{ product.category_name }}</span>
            </td>
            <td>
              <span class="price-text">${{ Number(product.base_price).toFixed(2) }}</span>
            </td>
            <td>
              <span class="unit-text">{{ product.unit }}</span>
            </td>
            <td>
              <span class="barcode-text">{{ product.barcode || '-' }}</span>
            </td>
            <td>
              <span
                class="status-chip"
                :class="product.is_active ? 'chip-green' : 'chip-gray'"
              >
                {{ product.is_active ? '✓ Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-right">
              <div class="action-btn-group">
                <button
                  class="btn btn-sm btn-outline"
                  @click="openEditModal(product)"
                  title="Edit Product"
                >
                  ✏️ Edit
                </button>

                <button
                  class="btn btn-sm btn-danger"
                  @click="confirmDelete(product)"
                  title="Delete Product"
                >
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CREATE MODAL -->
    <div v-if="showCreateModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <div>
            <h3>Create New Product</h3>
            <p>Add a new product to the global catalog</p>
          </div>
          <button class="close-btn" @click="showCreateModal = false">×</button>
        </div>

        <form @submit.prevent="handleCreateProduct" class="modal-form">
          <div v-if="modalError" class="modal-error-alert">
            ⚠️ {{ modalError }}
          </div>

          <div class="form-group">
            <label>Product Name *</label>
            <input
              type="text"
              v-model="productForm.name"
              required
              placeholder="e.g. Fresh Tomatoes"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea
              v-model="productForm.description"
              placeholder="Brief description of the product"
              class="form-input"
              rows="2"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Category *</label>
              <select v-model="productForm.category_id" required class="form-input">
                <option :value="null" disabled>Select category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Base Price (ETB) *</label>
              <input
                type="number"
                v-model.number="productForm.base_price"
                required
                min="0.01"
                step="0.5"
                placeholder="0.00"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Unit *</label>
              <input
                type="text"
                v-model="productForm.unit"
                required
                placeholder="e.g. kg, piece, liter"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>Barcode / SKU (Optional)</label>
              <input
                type="text"
                v-model="productForm.barcode"
                placeholder="Auto-generated if blank"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Product Image (Optional)</label>

            <!-- Image Preview Card -->
            <div v-if="productForm.image_url" class="img-preview-card">
              <img :src="formatImageUrl(productForm.image_url)" alt="Product preview" class="preview-thumb" />
              <div class="preview-actions">
                <span class="preview-badge">✓ Image Selected</span>
                <button type="button" class="btn-remove-thumb" @click="productForm.image_url = ''">✕ Remove</button>
              </div>
            </div>

            <!-- Upload File or URL -->
            <div class="image-upload-wrapper">
              <input
                type="file"
                ref="imageInput"
                @change="handleImageUpload"
                accept="image/*"
                class="file-input"
                id="adminProdImageUpload"
              />
              <label for="adminProdImageUpload" class="file-label">
                <span>📷 Upload Image from Device</span>
              </label>

              <input
                type="text"
                v-model="productForm.image_url"
                placeholder="Or paste image link (https://...)"
                class="form-input"
              />
            </div>

            <!-- Samples -->
            <div class="image-presets">
              <small>Sample Photos:</small>
              <button
                type="button"
                v-for="p in quickImagePresets"
                :key="p.label"
                class="preset-btn"
                @click="selectPresetImage(p)"
              >
                {{ p.label }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="productForm.is_active" />
              <span>Product is active and immediately visible to customers</span>
            </label>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showCreateModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Registering...' : '✓ Create Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- PRODUCT ADDED SUCCESS MODAL -->
    <div v-if="isSuccessModalOpen && newlyCreatedProduct" class="modal-backdrop" @click.self="isSuccessModalOpen = false">
      <div class="modal-box success-modal-box">
        <div class="success-modal-header">
          <div class="success-icon-badge">✅</div>
          <h3>Product Registered Successfully!</h3>
          <p>Product is registered in database and available on the customer storefront for sale.</p>
        </div>

        <div class="success-product-card">
          <img v-if="newlyCreatedProduct.image_url" :src="formatImageUrl(newlyCreatedProduct.image_url)" :alt="newlyCreatedProduct.name" class="success-prod-img" />
          <div v-else class="success-prod-img placeholder-box">📦</div>
          <div class="success-prod-details">
            <h4 class="success-prod-title">{{ newlyCreatedProduct.name }}</h4>
            <div class="success-prod-meta">
              <span class="meta-tag">Price: ETB {{ Number(newlyCreatedProduct.base_price || newlyCreatedProduct.price || 50).toFixed(2) }}</span>
              <span class="meta-tag">Unit: {{ newlyCreatedProduct.unit || 'unit' }}</span>
              <span class="meta-tag status-active">🟢 Active in Catalog</span>
            </div>
            <p class="success-prod-note">Published across all store branches in Addis Ababa.</p>
          </div>
        </div>

        <div class="success-modal-actions">
          <router-link to="/products" target="_blank" class="btn btn-customer-cta">
            <span>🛒</span> View on Customer Products Page ↗
          </router-link>
          <button type="button" class="btn btn-outline" @click="isSuccessModalOpen = false; openCreateModal()">
            <span>➕</span> Create Another Product
          </button>
          <button type="button" class="btn btn-secondary" @click="isSuccessModalOpen = false">
            Done
          </button>
        </div>
      </div>
    </div>

    <!-- EDIT MODAL -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <div>
            <h3>Edit Product</h3>
            <p>Update product information</p>
          </div>
          <button class="close-btn" @click="showEditModal = false">×</button>
        </div>

        <form @submit.prevent="handleUpdateProduct" class="modal-form">
          <div class="form-group">
            <label>Product Name *</label>
            <input
              type="text"
              v-model="productForm.name"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea
              v-model="productForm.description"
              class="form-input"
              rows="3"
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Category *</label>
              <select v-model="productForm.category_id" required class="form-input">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Base Price *</label>
              <input
                type="number"
                v-model="productForm.base_price"
                required
                min="0"
                step="0.01"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Unit *</label>
              <input
                type="text"
                v-model="productForm.unit"
                required
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>Barcode</label>
              <input
                type="text"
                v-model="productForm.barcode"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-group">
            <label>Product Image</label>
            <div v-if="productForm.image_url" class="img-preview-card" style="margin-bottom: 0.5rem;">
              <img :src="formatImageUrl(productForm.image_url)" alt="Product preview" class="preview-thumb" />
              <div class="preview-actions">
                <span class="preview-badge">✓ Current Image</span>
                <button type="button" class="btn-remove-thumb" @click="productForm.image_url = ''">✕ Remove</button>
              </div>
            </div>
            <input
              type="text"
              v-model="productForm.image_url"
              placeholder="Image URL or relative path (/storage/...)"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="productForm.is_active" />
              <span>Product is active and available</span>
            </label>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showEditModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Updating...' : 'Update Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div v-if="showDeleteConfirmModal" class="modal-backdrop">
      <div class="modal-box modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">⚠️ Confirm Deletion</h3>
          <button class="close-btn" @click="showDeleteConfirmModal = false">×</button>
        </div>

        <div class="modal-body">
          <p>Are you sure you want to delete <strong>{{ productToDelete?.name }}</strong>?</p>
          <p class="text-muted">This action cannot be undone. The product will be removed from the catalog.</p>
        </div>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-secondary"
            @click="showDeleteConfirmModal = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-danger"
            @click="executeDelete"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Deleting...' : 'Delete Product' }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
@import '../../assets/admin-common.css';

.tab-panel {
  padding: 2rem;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.panel-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}

.panel-header p {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.banner {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
}

.success-banner {
  background: #d1fae5;
  color: #065f46;
}

.error-banner {
  background: #fee2e2;
  color: #991b1b;
}

.filter-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.search-wrap {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  background: white;
}

.table-card {
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f8fafc;
}

.data-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.data-table td {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
  font-size: 0.875rem;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.product-img {
  width: 48px;
  height: 48px;
  border-radius: 6px;
  object-fit: cover;
}

.product-img-placeholder {
  width: 48px;
  height: 48px;
  border-radius: 6px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.product-name {
  display: block;
  color: #0f172a;
  font-weight: 500;
}

.product-desc {
  display: block;
  color: #64748b;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.price-text {
  font-weight: 600;
  color: #0f172a;
}

.unit-text,
.barcode-text {
  color: #64748b;
  font-size: 0.875rem;
}

.status-chip {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.chip-green {
  background: #d1fae5;
  color: #065f46;
}

.chip-gray {
  background: #f1f5f9;
  color: #475569;
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.py-6 {
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

.text-muted {
  color: #94a3b8;
}

.action-btn-group {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #0f172a;
  color: white;
}

.btn-primary:hover {
  background: #1e293b;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8125rem;
}

.btn-outline {
  background: white;
  border: 1px solid #e2e8f0;
  color: #0f172a;
}

.btn-outline:hover {
  background: #f8fafc;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover {
  background: #b91c1c;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-box {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-sm {
  max-width: 400px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 0.25rem 0;
}

.modal-header p {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.close-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: #f1f5f9;
  color: #64748b;
  font-size: 1.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.close-btn:hover {
  background: #e2e8f0;
}

.modal-body {
  padding: 1.5rem;
}

.modal-body p {
  margin: 0 0 0.75rem 0;
  color: #0f172a;
}

.modal-form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.flex-1 {
  flex: 1;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0f172a;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
}

.form-input:focus {
  outline: none;
  border-color: #0f172a;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  cursor: pointer;
}

.modal-actions {
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.text-danger {
  color: #dc2626;
}

/* Header Action Group & Customer Link */
.header-action-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-customer-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #059669;
  color: #ffffff;
  border: 1px solid #059669;
  font-weight: 600;
  text-decoration: none;
  padding: 0.65rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: all 0.15s ease;
}

.btn-customer-link:hover {
  background: #047857;
  color: #ffffff;
}

.banner-link {
  margin-left: 1rem;
  color: #047857;
  font-weight: 700;
  text-decoration: underline;
}

/* In-Modal Error Alert */
.modal-error-alert {
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  margin-bottom: 1rem;
}

/* Image Upload & Preview */
.image-upload-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 0.35rem;
}

.file-input {
  display: none;
}

.file-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.6rem 1rem;
  background: #f8fafc;
  border: 1px dashed #94a3b8;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  color: #334155;
  transition: all 0.15s;
}

.file-label:hover {
  background: #f1f5f9;
  border-color: #0f172a;
}

.img-preview-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.65rem 0.85rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.preview-thumb {
  width: 56px;
  height: 56px;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid #86efac;
  background: #ffffff;
}

.preview-actions {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.preview-badge {
  font-size: 0.78rem;
  font-weight: 600;
  color: #15803d;
}

.btn-remove-thumb {
  font-size: 0.74rem;
  color: #dc2626;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  text-align: left;
  font-weight: 500;
  text-decoration: underline;
}

.image-presets {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  margin-top: 0.4rem;
}

.image-presets small {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

.preset-btn {
  font-size: 0.74rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.2rem 0.45rem;
  cursor: pointer;
  transition: all 0.15s;
}

.preset-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

/* Success Confirmation Modal Box */
.success-modal-box {
  max-width: 500px;
  text-align: center;
  padding: 2rem 1.75rem;
  border-radius: 14px;
}

.success-modal-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 1.25rem;
}

.success-icon-badge {
  font-size: 2.5rem;
  line-height: 1;
}

.success-modal-header h3 {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.success-modal-header p {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.success-product-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem;
  text-align: left;
  margin-bottom: 1.5rem;
}

.success-prod-img {
  width: 64px;
  height: 64px;
  border-radius: 8px;
  object-fit: cover;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-prod-img.placeholder-box {
  font-size: 1.8rem;
  background: #f1f5f9;
}

.success-prod-details {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.success-prod-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.success-prod-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.meta-tag {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  color: #334155;
}

.meta-tag.status-active {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.success-prod-note {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0.2rem 0 0 0;
}

.success-modal-actions {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.btn-customer-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: #059669;
  color: #ffffff;
  font-weight: 700;
  padding: 0.75rem 1.25rem;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.95rem;
  transition: background 0.15s ease;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
}

.btn-customer-cta:hover {
  background: #047857;
  color: #ffffff;
}
</style>
