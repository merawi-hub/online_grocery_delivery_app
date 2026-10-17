<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import api from '../../services/api';

const inventory = ref([]);
const categories = ref([]);
const searchQuery = ref('');
const selectedCategoryFilter = ref('');
const loading = ref(true);
const savingId = ref(null);
const feedbackMsg = ref('');
const feedbackType = ref('success');

// Modal States
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const editingItem = ref(null);
const isSubmitting = ref(false);
const addModalError = ref('');
const isSuccessModalOpen = ref(false);
const newlyCreatedProduct = ref(null);

// Unique Key Generator for Products (Auto & Unique)
function generateProductKey() {
  const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
  let rand = '';
  for (let i = 0; i < 6; i++) {
    rand += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return `PRD-${rand}`;
}

// Category Mode: 'existing' | 'new'
const categoryMode = ref('existing');
const showNewCategoryInput = ref(false);
const newCategoryName = ref('');
const imageInput = ref(null);

// New Product Form
const newProduct = reactive({
  name: '',
  category_id: '',
  sku: generateProductKey(),
  pricing_type: 'fixed_unit', // 'fixed_unit' (piece/pack) or 'weighted' (kg)
  unit_of_measure: 'piece',
  step_weight_grams: 500,
  price: 50.00,
  discount_price: null,
  stock_quantity: 25,
  is_perishable: false,
  is_in_stock: true,
  image_url: '',
});

function regenerateProductKey() {
  newProduct.sku = generateProductKey();
  showFeedback(`New unique Product Key generated: ${newProduct.sku}`, 'success');
}

function setPricingType(type) {
  newProduct.pricing_type = type;
  if (type === 'weighted') {
    newProduct.unit_of_measure = 'kg';
    if (!newProduct.step_weight_grams) newProduct.step_weight_grams = 500;
  } else {
    if (newProduct.unit_of_measure === 'kg') {
      newProduct.unit_of_measure = 'piece';
    }
  }
}

const formattedPricePreview = computed(() => {
  const p = Number(newProduct.price);
  if (isNaN(p) || p <= 0) return 'ETB 0.00';
  const unitStr = newProduct.pricing_type === 'weighted' ? '/ kg' : `/${newProduct.unit_of_measure || 'piece'}`;
  return `ETB ${p.toFixed(2)} ${unitStr}`;
});

// Edit Product Form
const editForm = reactive({
  id: null,
  name: '',
  category_id: 1,
  pricing_type: 'fixed_unit',
  unit_of_measure: 'piece',
  price: 0,
  discount_price: null,
  stock_quantity: 0,
  is_in_stock: true,
  is_perishable: false,
  image_url: '',
});

const editImageInput = ref(null);

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

function handleTableImageError(e) {
  e.target.style.display = 'none';
  if (e.target.nextElementSibling) {
    e.target.nextElementSibling.style.display = 'inline-flex';
  }
}

const isUploadingImage = ref(false);

function processAndUploadImage(file) {
  return new Promise((resolve, reject) => {
    if (!file) return resolve('');
    const reader = new FileReader();
    reader.onload = (e) => {
      const img = new Image();
      img.onload = () => {
        const maxDim = 600;
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

        // Upload directly as multipart/form-data to save file directly to storage/products
        canvas.toBlob(async (blob) => {
          if (blob) {
            const formData = new FormData();
            formData.append('image', blob, 'product.jpg');
            try {
              const res = await api.post('/manager/upload-image', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
              });
              if (res.data && res.data.url) {
                resolve(res.data.url);
                return;
              }
            } catch (uploadErr) {
              console.warn('API direct upload failed, fallback to compressed data URL', uploadErr);
            }
          }
          // Fallback to compact base64
          resolve(canvas.toDataURL('image/jpeg', 0.72));
        }, 'image/jpeg', 0.75);
      };
      img.onerror = () => {
        resolve(e.target.result);
      };
      img.src = e.target.result;
    };
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

async function loadData() {
  loading.value = true;
  try {
    const [invRes, statsRes] = await Promise.all([
      api.get('/manager/inventory'),
      api.get('/manager/stats'),
    ]);
    inventory.value = invRes.data;
    categories.value = statsRes.data.categories || [
      { id: 1, name: 'Fresh Fruits & Vegetables' },
      { id: 2, name: 'Dairy & Farm Eggs' },
      { id: 3, name: 'Bakery & Pastries' },
      { id: 4, name: 'Butcher & Fresh Meat' },
      { id: 5, name: 'Pantry & Beverages' },
    ];
  } catch (err) {
    console.error('Failed to load inventory', err);
  } finally {
    loading.value = false;
  }
}

const filteredInventory = computed(() => {
  return inventory.value.filter((item) => {
    const matchesSearch = !searchQuery.value ||
      item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.sku.toLowerCase().includes(searchQuery.value.toLowerCase());

    const matchesCategory = !selectedCategoryFilter.value ||
      item.category_id === Number(selectedCategoryFilter.value) ||
      item.category === selectedCategoryFilter.value;

    return matchesSearch && matchesCategory;
  });
});

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

// Quick Add Category Handler
async function handleQuickAddCategory() {
  if (!newCategoryName.value.trim()) return;
  try {
    const res = await api.post('/manager/categories', {
      name: newCategoryName.value.trim(),
      is_active: true,
    });
    const createdCat = res.data.category;
    await loadCategories();
    newProduct.category_id = createdCat.id;
    categoryMode.value = 'existing';
    showNewCategoryInput.value = false;
    newCategoryName.value = '';
    showFeedback(`Category "${createdCat.name}" created and selected`, 'success');
    broadcastCatalogUpdate('category_added');
  } catch (err) {
    showFeedback('Failed to create category', 'error');
  }
}

// Add Product Handler
async function handleCreateProduct() {
  addModalError.value = '';

  // 1. Client-side input validations with specific guidance
  if (!newProduct.name || !newProduct.name.trim()) {
    addModalError.value = 'Product Name is required.';
    return;
  }

  let categoryId = newProduct.category_id;
  
  // If creating a new category on the fly, create it first
  if ((categoryMode.value === 'new' || showNewCategoryInput.value) && newCategoryName.value.trim()) {
    isSubmitting.value = true;
    try {
      const categoryRes = await api.post('/manager/categories', {
        name: newCategoryName.value.trim(),
        is_active: true,
      });
      categoryId = categoryRes.data.category.id;
      newProduct.category_id = categoryId;
      categoryMode.value = 'existing';
      showNewCategoryInput.value = false;
      newCategoryName.value = '';
      
      // Reload categories to include the new one
      await loadCategories();
      broadcastCatalogUpdate('category_added');
    } catch (catErr) {
      isSubmitting.value = false;
      addModalError.value = 'Failed to create category. Please check the name or choose an existing category.';
      return;
    }
  }
  
  // Validate category selection
  if (!categoryId) {
    addModalError.value = 'Please select a Category from the dropdown or click "+ Create New Category".';
    return;
  }

  const priceVal = Number(newProduct.price);
  if (!priceVal || isNaN(priceVal) || priceVal <= 0) {
    addModalError.value = 'Please enter a valid price (greater than 0 ETB). Both integers and decimals are supported.';
    return;
  }

  const stockVal = Number(newProduct.stock_quantity);
  if (isNaN(stockVal) || stockVal < 0) {
    addModalError.value = 'Please enter a valid stock quantity available in store (0 or greater).';
    return;
  }

  // Ensure unique product key
  if (!newProduct.sku || !newProduct.sku.trim()) {
    newProduct.sku = generateProductKey();
  }

  isSubmitting.value = true;
  try {
    const unitMeasure = newProduct.pricing_type === 'weighted' ? 'kg' : (newProduct.unit_of_measure || 'piece');
    const payload = {
      name: newProduct.name.trim(),
      category_id: categoryId,
      sku: newProduct.sku.trim(),
      pricing_type: newProduct.pricing_type || 'fixed_unit',
      unit_of_measure: unitMeasure,
      step_weight_grams: newProduct.pricing_type === 'weighted' ? (Number(newProduct.step_weight_grams) || 500) : null,
      price: priceVal,
      discount_price: newProduct.discount_price ? Number(newProduct.discount_price) : null,
      stock_quantity: stockVal,
      is_perishable: !!newProduct.is_perishable,
      is_in_stock: newProduct.is_in_stock !== false,
      image_url: newProduct.image_url || null,
    };

    // Create the product
    const res = await api.post('/manager/products', payload);

    const createdProd = res.data.product || {
      name: newProduct.name,
      sku: newProduct.sku,
      price: priceVal,
      stock_quantity: stockVal,
      pricing_type: newProduct.pricing_type,
      unit_of_measure: unitMeasure,
      image_url: newProduct.image_url,
      category: categories.value.find(c => c.id === categoryId) || { name: 'Catalog Item' },
    };

    newlyCreatedProduct.value = createdProd;
    isAddModalOpen.value = false;
    isSuccessModalOpen.value = true;
    showFeedback(`Product "${createdProd.name}" added to catalog`, 'success');
    resetNewProduct();
    await loadData();
    broadcastCatalogUpdate('product_added');
  } catch (err) {
    console.error('Failed to add product:', err);
    let errorMsg = 'Failed to add product.';
    if (!err.response) {
      errorMsg = 'Cannot connect to backend server. Please verify php artisan serve is running on port 8000.';
    } else if (err.response.status === 413) {
      errorMsg = 'The uploaded photo is too large for the server. Please select a smaller photo.';
    } else if (err.response.status === 422) {
      if (err.response.data?.errors) {
        errorMsg = 'Validation issue: ' + Object.values(err.response.data.errors).flat().join(' • ');
      } else if (err.response.data?.message) {
        errorMsg = err.response.data.message;
      } else {
        errorMsg = 'Invalid input values. Please review required fields.';
      }
    } else if (err.response.data?.message) {
      errorMsg = err.response.data.message;
    } else {
      errorMsg = `Server error (${err.response.status}). Please check backend status.`;
    }
    addModalError.value = errorMsg;
    showFeedback(errorMsg, 'error');
  } finally {
    isSubmitting.value = false;
  }
}

// Handle Image Upload for New Product
async function handleImageUpload(event) {
  const file = event.target.files[0];
  if (file) {
    isUploadingImage.value = true;
    try {
      const url = await processAndUploadImage(file);
      newProduct.image_url = url;
      showFeedback('Product image attached', 'success');
    } catch (err) {
      console.error('Image processing failed:', err);
      showFeedback('Failed to process image. Please try again.', 'error');
    } finally {
      isUploadingImage.value = false;
    }
  }
}

function removeNewProductImage() {
  newProduct.image_url = '';
  if (imageInput.value) {
    imageInput.value.value = '';
  }
}

// Handle Image Upload for Edit Product
async function handleEditImageUpload(event) {
  const file = event.target.files[0];
  if (file) {
    isUploadingImage.value = true;
    try {
      const url = await processAndUploadImage(file);
      editForm.image_url = url;
      showFeedback('Product image attached', 'success');
    } catch (err) {
      console.error('Image processing failed:', err);
      showFeedback('Failed to process image. Please try again.', 'error');
    } finally {
      isUploadingImage.value = false;
    }
  }
}

function removeEditImage() {
  editForm.image_url = '';
  if (editImageInput.value) {
    editImageInput.value.value = '';
  }
}

// Load categories separately
async function loadCategories() {
  try {
    const statsRes = await api.get('/manager/stats');
    categories.value = statsRes.data.categories || [];
  } catch (err) {
    console.error('Failed to load categories', err);
  }
}

// Open Edit Modal
function openEditModal(item) {
  editingItem.value = item;
  editForm.id = item.id;
  editForm.name = item.name;
  editForm.category_id = item.category_id;
  editForm.pricing_type = item.pricing_type;
  editForm.unit_of_measure = item.unit_of_measure;
  editForm.price = item.price;
  editForm.discount_price = item.discount_price;
  editForm.stock_quantity = item.stock_quantity;
  editForm.is_in_stock = item.is_in_stock;
  editForm.is_perishable = item.is_perishable;
  editForm.image_url = item.image_url || '';
  isEditModalOpen.value = true;
}

// Submit Edit Product
async function handleUpdateProduct() {
  isSubmitting.value = true;
  try {
    const targetId = editingItem.value?.product_id || editForm.id;
    await api.put(`/manager/products/${targetId}`, {
      name: editForm.name,
      category_id: editForm.category_id,
      pricing_type: editForm.pricing_type,
      unit_of_measure: editForm.unit_of_measure,
      price: editForm.price,
      discount_price: editForm.discount_price || null,
      stock_quantity: editForm.stock_quantity,
      is_in_stock: editForm.is_in_stock,
      is_perishable: editForm.is_perishable,
      image_url: editForm.image_url || null,
    });

    showFeedback(`Updated "${editForm.name}" successfully`, 'success');
    isEditModalOpen.value = false;
    await loadData();
    broadcastCatalogUpdate('product_updated');
  } catch (err) {
    showFeedback('Failed to update product.', 'error');
  } finally {
    isSubmitting.value = false;
  }
}

// Inline In-Stock Toggle
async function toggleStock(item) {
  item.is_in_stock = !item.is_in_stock;
  await saveInline(item);
}

// Inline Quick Save
async function saveInline(item) {
  savingId.value = item.id;
  try {
    await api.put(`/manager/inventory/${item.id}`, {
      price: item.price,
      discount_price: item.discount_price,
      stock_quantity: item.stock_quantity,
      is_in_stock: item.is_in_stock,
    });
    showFeedback(`Saved ${item.name}`, 'success');
    broadcastCatalogUpdate('product_updated');
  } catch (err) {
    showFeedback('Error saving updates.', 'error');
  } finally {
    savingId.value = null;
  }
}

// Delete Product
async function handleDeleteProduct(item) {
  if (!confirm(`Are you sure you want to remove "${item.name}" from inventory?`)) return;
  try {
    await api.delete(`/manager/products/${item.id}`);
    showFeedback(`Removed "${item.name}" from catalog`, 'success');
    await loadData();
    broadcastCatalogUpdate('product_deleted');
  } catch (err) {
    showFeedback('Failed to delete product.', 'error');
  }
}

function showFeedback(msg, type = 'success') {
  feedbackMsg.value = msg;
  feedbackType.value = type;
  setTimeout(() => { feedbackMsg.value = ''; }, 3000);
}

function resetNewProduct() {
  newProduct.name = '';
  newProduct.category_id = categories.value.length > 0 ? categories.value[0].id : '';
  newProduct.sku = generateProductKey();
  newProduct.pricing_type = 'fixed_unit';
  newProduct.unit_of_measure = 'piece';
  newProduct.step_weight_grams = 500;
  newProduct.price = 50.00;
  newProduct.discount_price = null;
  newProduct.stock_quantity = 25;
  newProduct.is_perishable = false;
  newProduct.is_in_stock = true;
  newProduct.image_url = '';
  categoryMode.value = 'existing';
  showNewCategoryInput.value = false;
  newCategoryName.value = '';
  if (imageInput.value) {
    imageInput.value.value = '';
  }
}

function openAddModal() {
  addModalError.value = '';
  if (!newProduct.sku) {
    newProduct.sku = generateProductKey();
  }
  if (categories.value.length > 0 && !newProduct.category_id) {
    newProduct.category_id = categories.value[0].id;
  }
  isAddModalOpen.value = true;
}

function handleAddAnotherProduct() {
  isSuccessModalOpen.value = false;
  resetNewProduct();
  openAddModal();
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="manager-inventory-page">
    <!-- Header with Action -->
    <div class="page-head">
      <div>
        <h1 class="page-title">Products & Inventory</h1>
        <p class="page-sub">
          Manage product catalog, prices, shelf quantities, and item availability.
        </p>
      </div>

      <div class="head-actions">
        <router-link to="/products" target="_blank" class="btn btn-customer-link">
          Customer Storefront ↗
        </router-link>
        <router-link to="/admin123/orders" class="btn btn-outline">
          Orders Queue
        </router-link>
        <router-link to="/admin123/dashboard" class="btn btn-outline">
          Dashboard
        </router-link>
        <button class="btn btn-primary" @click="openAddModal">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Add Product
        </button>
      </div>
    </div>

    <!-- Feedback Message -->
    <div v-if="feedbackMsg" class="feedback-banner" :class="feedbackType">
      <span>{{ feedbackMsg }}</span>
      <router-link to="/products" target="_blank" class="banner-customer-link">
        View on Customer Storefront ↗
      </router-link>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <span class="search-icon">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search products or SKU..."
        />
        <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''" aria-label="Clear search">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <div class="filter-box">
        <label>Aisle:</label>
        <select v-model="selectedCategoryFilter" class="select-input">
          <option value="">All Categories ({{ inventory.length }})</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Products Table -->
    <div class="table-container">
      <table class="inventory-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Type</th>
            <th>Price (ETB)</th>
            <th>Stock</th>
            <th>Availability</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="empty-state">Loading inventory catalog...</td>
          </tr>
          <tr v-else-if="filteredInventory.length === 0">
            <td colspan="7" class="empty-state">No products found matching your search.</td>
          </tr>
          <tr v-for="item in filteredInventory" :key="item.id">
            <!-- Col 1: Product Details -->
            <td>
              <div class="product-cell">
                <img :src="formatImageUrl(item.image_url)" :alt="item.name" class="product-img" @error="handleTableImageError" />
                <div>
                  <strong class="product-name">{{ item.name }}</strong>
                  <div class="product-meta">
                    <span class="sku-tag">SKU: {{ item.sku }}</span>
                    <span v-if="item.is_perishable" class="perish-tag">• Perishable</span>
                  </div>
                </div>
              </div>
            </td>

            <!-- Col 2: Category -->
            <td>
              <span class="category-text">{{ item.category }}</span>
            </td>

            <!-- Col 3: Type -->
            <td>
              <span class="type-text">
                {{ item.pricing_type === 'weighted' ? 'Weighted (kg)' : 'Unit' }}
              </span>
            </td>

            <!-- Col 4: Price Inline -->
            <td>
              <div class="inline-input-group">
                <span class="curr-prefix">ETB</span>
                <input
                  type="number"
                  step="0.5"
                  v-model.number="item.price"
                  @blur="saveInline(item)"
                  @keyup.enter="saveInline(item)"
                  class="inline-num-input"
                  title="Click to edit price"
                />
                <span class="unit-suffix">/{{ item.unit_of_measure }}</span>
              </div>
            </td>

            <!-- Col 5: Stock Inline -->
            <td>
              <div class="inline-input-group">
                <input
                  type="number"
                  step="1"
                  v-model.number="item.stock_quantity"
                  @blur="saveInline(item)"
                  @keyup.enter="saveInline(item)"
                  class="inline-num-input stock-input"
                  title="Click to edit stock"
                />
                <span class="unit-suffix">{{ item.unit_of_measure }}</span>
              </div>
            </td>

            <!-- Col 6: In-Stock Toggle -->
            <td>
              <button
                type="button"
                class="stock-toggle"
                :class="{ 'in-stock': item.is_in_stock }"
                @click="toggleStock(item)"
                title="Toggle shelf availability"
              >
                <span class="toggle-indicator"></span>
                <span>{{ item.is_in_stock ? 'In Stock' : 'Out of Stock' }}</span>
              </button>
            </td>

            <!-- Col 7: Actions -->
            <td class="text-right">
              <div class="actions-group">
                <button class="btn btn-outline btn-sm" @click="openEditModal(item)">
                  Edit
                </button>
                <button class="btn btn-danger btn-sm" @click="handleDeleteProduct(item)" title="Delete Product">
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODAL 1: ADD PRODUCT -->
    <div v-if="isAddModalOpen" class="modal-overlay" @click.self="isAddModalOpen = false">
      <div class="modal-sheet modal-box add-product-modal">
        <div class="modal-head">
          <div>
            <h3 class="modal-title">Add New Product</h3>
            <p class="modal-subtitle">Enter product specifications, pricing model, and stock availability.</p>
          </div>
          <button type="button" class="close-btn" @click="isAddModalOpen = false" aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleCreateProduct" class="modal-form-wrapper">
          <div class="modal-body">
            <div v-if="addModalError" class="modal-error-alert">
              <svg class="error-alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <span class="error-alert-text">{{ addModalError }}</span>
              <button type="button" class="error-alert-dismiss" @click="addModalError = ''" aria-label="Dismiss error">✕</button>
            </div>

            <!-- ROW 1: Product Name & SKU -->
            <div class="form-row">
              <div class="form-group flex-2">
                <label class="form-label">Product Name <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="newProduct.name" 
                  required 
                  class="form-input font-medium" 
                />
              </div>

              <!-- Product Key / SKU -->
              <div class="form-group flex-1">
                <div class="label-row">
                  <label class="form-label">Product Key <span class="required">*</span></label>
                  <button 
                    type="button" 
                    class="btn-text-action" 
                    @click="regenerateProductKey" 
                    title="Generate new unique product key"
                  >
                    Regenerate
                  </button>
                </div>
                <div class="key-input-container">
                  <input 
                    type="text" 
                    v-model="newProduct.sku" 
                    required 
                    class="form-input key-input" 
                    title="Unique product identifier code"
                  />
                  <button 
                    type="button" 
                    class="btn-regenerate-icon" 
                    @click="regenerateProductKey" 
                    title="Generate new key"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 2v6h-6"></path>
                      <path d="M3 12a9 9 0 0 1 15-6.7L21 8"></path>
                      <path d="M3 22v-6h6"></path>
                      <path d="M21 12a9 9 0 0 1-15 6.7L3 16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- ROW 2: Category (Existing or New) -->
            <div class="form-group category-section">
              <div class="category-header-wrap">
                <label class="form-label">Product Category <span class="required">*</span></label>
                <div class="segmented-control">
                  <button 
                    type="button" 
                    class="segment-btn" 
                    :class="{ active: categoryMode === 'existing' }"
                    @click="categoryMode = 'existing'"
                  >
                    Select Existing
                  </button>
                  <button 
                    type="button" 
                    class="segment-btn" 
                    :class="{ active: categoryMode === 'new' }"
                    @click="categoryMode = 'new'"
                  >
                    Create New
                  </button>
                </div>
              </div>

              <!-- Existing Category Dropdown -->
              <div v-if="categoryMode === 'existing'" class="existing-cat-box">
                <select 
                  v-model="newProduct.category_id" 
                  class="form-input form-select"
                  required
                >
                  <option value="" disabled>Select category</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                  </option>
                </select>
              </div>

              <!-- New Category Input -->
              <div v-else class="new-cat-box">
                <div class="new-cat-input-row">
                  <input 
                    type="text" 
                    v-model="newCategoryName" 
                    class="form-input"
                    @keyup.enter.prevent="handleQuickAddCategory"
                    autoFocus
                  />
                  <button 
                    type="button" 
                    class="btn btn-save-inline-cat"
                    @click="handleQuickAddCategory"
                    :disabled="!newCategoryName.trim()"
                  >
                    Save Category
                  </button>
                </div>
              </div>
            </div>

            <!-- ROW 3: Pricing Mode Selector -->
            <div class="form-group">
              <label class="form-label">Pricing Model <span class="required">*</span></label>
              <div class="pricing-selector-grid">
                <div 
                  class="pricing-option-card" 
                  :class="{ selected: newProduct.pricing_type === 'fixed_unit' }"
                  @click="setPricingType('fixed_unit')"
                  role="button"
                  tabindex="0"
                >
                  <div class="custom-radio">
                    <div class="custom-radio-dot" v-if="newProduct.pricing_type === 'fixed_unit'"></div>
                  </div>
                  <div class="pricing-option-text">
                    <span class="pricing-option-title">Fixed Quantity / Unit</span>
                    <span class="pricing-option-desc">Price per piece, pack, bottle, or box</span>
                  </div>
                </div>

                <div 
                  class="pricing-option-card" 
                  :class="{ selected: newProduct.pricing_type === 'weighted' }"
                  @click="setPricingType('weighted')"
                  role="button"
                  tabindex="0"
                >
                  <div class="custom-radio">
                    <div class="custom-radio-dot" v-if="newProduct.pricing_type === 'weighted'"></div>
                  </div>
                  <div class="pricing-option-text">
                    <span class="pricing-option-title">Per Kilogram (Weight)</span>
                    <span class="pricing-option-desc">Price per kg for fresh produce, fruits & meat</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- ROW 4: Unit of Measure & Scale Step (if weighted) -->
            <div class="form-row">
              <div class="form-group flex-1" v-if="newProduct.pricing_type === 'fixed_unit'">
                <label class="form-label">Unit of Measure <span class="required">*</span></label>
                <select v-model="newProduct.unit_of_measure" class="form-input form-select">
                  <option value="piece">Piece</option>
                  <option value="pack">Pack</option>
                  <option value="bottle">Bottle</option>
                  <option value="box">Box</option>
                  <option value="can">Can</option>
                  <option value="tray">Tray</option>
                  <option value="liter">Liter</option>
                  <option value="bundle">Bundle</option>
                </select>
              </div>

              <div class="form-group flex-1" v-if="newProduct.pricing_type === 'weighted'">
                <label class="form-label">Unit of Measure</label>
                <input type="text" value="Kilogram (kg)" disabled class="form-input input-disabled" />
              </div>

              <div class="form-group flex-1" v-if="newProduct.pricing_type === 'weighted'">
                <label class="form-label">Customer Scale Step</label>
                <select v-model.number="newProduct.step_weight_grams" class="form-input form-select">
                  <option :value="500">500g (Half Kilo)</option>
                  <option :value="250">250g (Quarter Kilo)</option>
                  <option :value="1000">1,000g (1 Kilo)</option>
                  <option :value="100">100g (Fine Produce)</option>
                </select>
              </div>
            </div>

            <!-- ROW 5: Price (ETB) and Stock Quantity -->
            <div class="form-row">
              <!-- Price Field -->
              <div class="form-group flex-1">
                <div class="label-row">
                  <label class="form-label">
                    {{ newProduct.pricing_type === 'weighted' ? 'Price per Kilo *' : 'Unit Price *' }}
                  </label>
                  <span class="preview-tag">{{ formattedPricePreview }}</span>
                </div>
                <div class="currency-input-wrap">
                  <span class="currency-addon">ETB</span>
                  <input 
                    type="number" 
                    step="0.01" 
                    min="0.01" 
                    v-model.number="newProduct.price" 
                    required 
                    class="form-input input-with-addon" 
                  />
                </div>
              </div>

              <!-- Stock Quantity -->
              <div class="form-group flex-1">
                <div class="label-row">
                  <label class="form-label">Stock Quantity <span class="required">*</span></label>
                  <span class="unit-tag">
                    {{ newProduct.pricing_type === 'weighted' ? 'kg' : (newProduct.unit_of_measure || 'units') }}
                  </span>
                </div>
                <input 
                  type="number" 
                  step="any" 
                  min="0" 
                  v-model.number="newProduct.stock_quantity" 
                  required 
                  class="form-input" 
                />
              </div>
            </div>

            <!-- ROW 6: Product Photo -->
            <div class="form-group">
              <label class="form-label">Product Photo</label>

              <!-- Image Preview Card if uploaded -->
              <div v-if="newProduct.image_url" class="image-preview-box">
                <img :src="formatImageUrl(newProduct.image_url)" alt="Product preview" class="preview-thumb" @error="handleTableImageError" />
                <div class="preview-info">
                  <span class="preview-title">Photo Attached</span>
                  <span class="preview-desc">Optimized and stored for catalog</span>
                </div>
                <div class="preview-actions">
                  <label for="imageUpload" class="btn-text">Change</label>
                  <span class="action-divider">|</span>
                  <button type="button" class="btn-text danger" @click="removeNewProductImage">Remove</button>
                </div>
              </div>

              <!-- Upload Dropzone -->
              <div v-else class="upload-area">
                <input 
                  type="file" 
                  ref="imageInput"
                  @change="handleImageUpload" 
                  accept="image/png, image/jpeg, image/webp, image/jpg"
                  class="file-input-hidden"
                  id="imageUpload"
                  :disabled="isUploadingImage"
                />
                <label for="imageUpload" class="upload-card" :class="{ 'uploading': isUploadingImage }">
                  <div class="upload-icon-box">
                    <svg v-if="!isUploadingImage" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                      <circle cx="9" cy="9" r="2"/>
                      <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    <div v-else class="spinner-small"></div>
                  </div>
                  <div class="upload-content">
                    <span class="upload-title">{{ isUploadingImage ? 'Uploading image...' : 'Upload product photo' }}</span>
                    <span class="upload-note">PNG, JPG, or WEBP from device</span>
                  </div>
                </label>
              </div>
            </div>

            <!-- ROW 7: Settings & Availability -->
            <div class="settings-panel">
              <label class="custom-check-item">
                <input type="checkbox" v-model="newProduct.is_perishable" class="native-check" />
                <div class="check-text-group">
                  <span class="check-title">Perishable Item</span>
                  <span class="check-desc">Requires refrigeration or cool temperature handling</span>
                </div>
              </label>
              <label class="custom-check-item">
                <input type="checkbox" v-model="newProduct.is_in_stock" class="native-check" />
                <div class="check-text-group">
                  <span class="check-title">Available for Sale</span>
                  <span class="check-desc">Display immediately on customer storefront</span>
                </div>
              </label>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="isAddModalOpen = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-button"></span>
              <span>{{ isSubmitting ? 'Adding Product...' : 'Add Product' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: PRODUCT ADDED SUCCESS MODAL -->
    <div v-if="isSuccessModalOpen && newlyCreatedProduct" class="modal-overlay" @click.self="isSuccessModalOpen = false">
      <div class="modal-sheet success-modal-box">
        <div class="success-modal-header">
          <div class="success-check-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <h3>Product Added Successfully</h3>
          <p>Product is saved to inventory and live on customer storefront.</p>
        </div>

        <div class="success-product-card">
          <img 
            :src="formatImageUrl(newlyCreatedProduct.image_url)" 
            :alt="newlyCreatedProduct.name" 
            class="success-prod-img" 
            @error="handleTableImageError" 
          />
          <div class="success-prod-details">
            <div class="success-key-badge">{{ newlyCreatedProduct.sku }}</div>
            <h4 class="success-prod-title">{{ newlyCreatedProduct.name }}</h4>
            <div class="success-prod-meta">
              <span class="meta-tag meta-price">
                ETB {{ Number(newlyCreatedProduct.price || newProduct.price || 0).toFixed(2) }} / {{ newlyCreatedProduct.pricing_type === 'weighted' ? 'kg' : (newlyCreatedProduct.unit_of_measure || 'piece') }}
              </span>
              <span class="meta-tag meta-stock">
                Stock: {{ newlyCreatedProduct.stock_quantity }} {{ newlyCreatedProduct.unit_of_measure || (newlyCreatedProduct.pricing_type === 'weighted' ? 'kg' : 'units') }}
              </span>
              <span class="meta-tag status-active">Active</span>
            </div>
          </div>
        </div>

        <div class="success-modal-actions">
          <button type="button" class="btn btn-primary btn-add-another" @click="handleAddAnotherProduct">
            Add Another Product
          </button>
          <div class="success-secondary-actions">
            <button type="button" class="btn btn-outline" @click="isSuccessModalOpen = false">
              View in Inventory
            </button>
            <router-link to="/products" target="_blank" class="btn btn-customer-cta">
              View Storefront ↗
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL 3: EDIT PRODUCT -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal-sheet modal-box">
        <div class="modal-head">
          <div>
            <h3 class="modal-title">Edit Product</h3>
            <p class="modal-subtitle">Update pricing, shelf stock, or product information.</p>
          </div>
          <button type="button" class="close-btn" @click="isEditModalOpen = false" aria-label="Close modal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleUpdateProduct" class="modal-form-wrapper">
          <div class="modal-body">
            <div class="form-group">
              <label class="form-label">Product Name <span class="required">*</span></label>
              <input type="text" v-model="editForm.name" required class="form-input font-medium" />
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label">Category <span class="required">*</span></label>
                <select v-model="editForm.category_id" class="form-input form-select" required>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </div>

              <div class="form-group flex-1">
                <label class="form-label">Pricing Type <span class="required">*</span></label>
                <select v-model="editForm.pricing_type" class="form-input form-select">
                  <option value="fixed_unit">Fixed Unit</option>
                  <option value="weighted">Per Kilogram (kg)</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label">Price (ETB) <span class="required">*</span></label>
                <div class="currency-input-wrap">
                  <span class="currency-addon">ETB</span>
                  <input type="number" step="0.01" v-model.number="editForm.price" required class="form-input input-with-addon" />
                </div>
              </div>

              <div class="form-group flex-1">
                <label class="form-label">Stock Quantity <span class="required">*</span></label>
                <input type="number" step="1" min="0" v-model.number="editForm.stock_quantity" required class="form-input" />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Product Photo</label>

              <!-- Image Preview Card if selected -->
              <div v-if="editForm.image_url" class="image-preview-box">
                <img :src="formatImageUrl(editForm.image_url)" alt="Product preview" class="preview-thumb" @error="handleTableImageError" />
                <div class="preview-info">
                  <span class="preview-title">Current Photo</span>
                  <span class="preview-desc">Saved on server</span>
                </div>
                <div class="preview-actions">
                  <label for="editImageUpload" class="btn-text">Change</label>
                  <span class="action-divider">|</span>
                  <button type="button" class="btn-text danger" @click="removeEditImage">Remove</button>
                </div>
              </div>

              <!-- Upload file -->
              <div v-else class="upload-area">
                <input 
                  type="file" 
                  ref="editImageInput"
                  @change="handleEditImageUpload" 
                  accept="image/*"
                  class="file-input-hidden"
                  id="editImageUpload"
                  :disabled="isUploadingImage"
                />
                <label for="editImageUpload" class="upload-card" :class="{ 'uploading': isUploadingImage }">
                  <div class="upload-icon-box">
                    <svg v-if="!isUploadingImage" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                      <circle cx="9" cy="9" r="2"/>
                      <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                    <div v-else class="spinner-small"></div>
                  </div>
                  <div class="upload-content">
                    <span class="upload-title">{{ isUploadingImage ? 'Uploading photo...' : 'Change product photo' }}</span>
                    <span class="upload-note">PNG, JPG, or WEBP from device</span>
                  </div>
                </label>
              </div>
            </div>

            <div class="settings-panel">
              <label class="custom-check-item">
                <input type="checkbox" v-model="editForm.is_in_stock" class="native-check" />
                <div class="check-text-group">
                  <span class="check-title">Available for Sale</span>
                  <span class="check-desc">Mark as available in store catalog</span>
                </div>
              </label>
            </div>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="isEditModalOpen = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              <span v-if="isSubmitting" class="spinner-button"></span>
              <span>{{ isSubmitting ? 'Saving...' : 'Save Changes' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.manager-inventory-page {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.head-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid #e5e7eb;
  background: white;
  color: #111827;
  transition: all 0.2s;
  text-decoration: none;
}

.btn:hover {
  background: #f8fafc;
  border-color: #0f172a;
}

.btn-outline {
  background: white;
  border: 1px solid #e5e7eb;
  color: #475569;
}

.btn-outline:hover {
  background: #f8fafc;
  border-color: #0f172a;
  color: #0f172a;
}

.btn-primary {
  background: #0f172a;
  color: white;
  border-color: #0f172a;
}

.btn-primary:hover {
  background: #1e293b;
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
  margin-top: 0.2rem;
}

.feedback-banner {
  padding: 0.65rem 0.85rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
}

.feedback-banner.success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

.feedback-banner.error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Toolbar */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 0.6rem 0.85rem;
}

.search-box {
  display: flex;
  align-items: center;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  font-size: 0.85rem;
  color: #9ca3af;
  margin-right: 0.4rem;
}

.search-box input {
  border: none;
  background: transparent;
  width: 100%;
  font-size: 0.85rem;
  color: #111827;
}

.clear-btn {
  font-size: 0.75rem;
  color: #9ca3af;
  cursor: pointer;
}

.clear-btn:hover {
  color: #111827;
}

.filter-box {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.filter-box label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.select-input {
  padding: 0.35rem 0.6rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  background: #ffffff;
  font-size: 0.8rem;
  color: #111827;
}

/* Table */
.table-container {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  overflow-x: auto;
}

.inventory-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.inventory-table th {
  padding: 0.65rem 1rem;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  letter-spacing: 0.04em;
}

.inventory-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  font-size: 0.825rem;
  vertical-align: middle;
}

.inventory-table tr:last-child td {
  border-bottom: none;
}

.product-cell {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.product-img {
  width: 38px;
  height: 38px;
  object-fit: cover;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
}

.product-name {
  display: block;
  font-size: 0.85rem;
  color: #111827;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.sku-tag {
  font-size: 0.7rem;
  color: #6b7280;
  font-family: monospace;
}

.perish-tag {
  font-size: 0.7rem;
  color: #166534;
}

.category-text {
  font-size: 0.8rem;
  color: #4b5563;
}

.type-text {
  font-size: 0.78rem;
  color: #4b5563;
}

.inline-input-group {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  padding: 0.2rem 0.45rem;
  background: #ffffff;
}

.inline-input-group:focus-within {
  border-color: #111827;
}

.curr-prefix {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6b7280;
}

.inline-num-input {
  width: 55px;
  border: none;
  font-size: 0.85rem;
  font-weight: 600;
  color: #111827;
  padding: 0;
}

.stock-input {
  width: 45px;
}

.unit-suffix {
  font-size: 0.7rem;
  color: #6b7280;
}

.stock-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.55rem;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 500;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.15s ease;
}

.stock-toggle.in-stock {
  border-color: #bbf7d0;
  background: #f0fdf4;
  color: #166534;
}

.toggle-indicator {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.actions-group {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.text-right {
  text-align: right;
}

.empty-state {
  text-align: center;
  padding: 2.5rem !important;
  color: #9ca3af;
}

/* Modals */
.modal-box {
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
}

.add-product-modal {
  max-width: 620px;
}

.modal-head {
  padding: 1.15rem 1.4rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-shrink: 0;
  background: #ffffff;
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.01em;
}

.modal-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0.2rem 0 0 0;
  line-height: 1.4;
}

.close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  margin-top: -0.15rem;
  margin-right: -0.25rem;
}

.close-btn:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.modal-form-wrapper {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

.modal-body {
  padding: 1.25rem 1.4rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  flex: 1;
  min-height: 0;
  overflow-y: auto;
}

.form-row {
  display: flex;
  gap: 0.85rem;
}

.flex-1 { flex: 1; }
.flex-2 { flex: 2; }

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #334155;
  margin-bottom: 0.1rem;
}

.required {
  color: #ef4444;
  margin-left: 2px;
}

.label-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.1rem;
}

.label-row .form-label {
  margin-bottom: 0;
}

.btn-text-action {
  font-size: 0.72rem;
  font-weight: 600;
  color: #2563eb;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}

.btn-text-action:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

.form-input {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #ffffff;
  font-size: 0.85rem;
  color: #0f172a;
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: #0f172a;
  box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
}

.font-medium {
  font-weight: 500;
}

.key-input-container {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.key-input {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-weight: 600;
  letter-spacing: 0.03em;
  background: #f8fafc;
  color: #334155;
}

.btn-regenerate-icon {
  padding: 0.55rem 0.65rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #ffffff;
  cursor: pointer;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-regenerate-icon:hover {
  background: #f8fafc;
  color: #0f172a;
  border-color: #94a3b8;
}

/* Category Section & Tabs */
.category-section {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.category-header-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.category-header-wrap .form-label {
  margin-bottom: 0;
}

.segmented-control {
  display: inline-flex;
  background: #f1f5f9;
  padding: 2px;
  border-radius: 6px;
}

.segment-btn {
  border: none;
  background: transparent;
  padding: 0.25rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.segment-btn.active {
  background: #ffffff;
  color: #0f172a;
  font-weight: 600;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
}

.existing-cat-box {
  width: 100%;
}

.form-select {
  cursor: pointer;
}

.new-cat-box {
  display: flex;
  flex-direction: column;
}

.new-cat-input-row {
  display: flex;
  gap: 0.5rem;
}

.btn-save-inline-cat {
  padding: 0.55rem 0.9rem;
  background: #0f172a;
  color: #ffffff;
  border: 1px solid #0f172a;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-save-inline-cat:hover:not(:disabled) {
  background: #1e293b;
}

.btn-save-inline-cat:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Pricing Mode Selector */
.pricing-selector-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.pricing-option-card {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.8rem 0.9rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.15s ease;
}

.pricing-option-card:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.pricing-option-card.selected {
  border-color: #0f172a;
  background: #f8fafc;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.custom-radio {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 1.5px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
  transition: border-color 0.15s;
}

.pricing-option-card.selected .custom-radio {
  border-color: #0f172a;
}

.custom-radio-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #0f172a;
}

.pricing-option-text {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.pricing-option-title {
  font-size: 0.825rem;
  font-weight: 600;
  color: #0f172a;
}

.pricing-option-desc {
  font-size: 0.715rem;
  color: #64748b;
  line-height: 1.35;
}

.input-disabled {
  background: #f1f5f9;
  color: #64748b;
  cursor: not-allowed;
}

/* Currency & Price preview */
.preview-tag {
  font-size: 0.72rem;
  font-weight: 600;
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #d1fae5;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
}

.unit-tag {
  font-size: 0.72rem;
  font-weight: 500;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
}

.currency-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-addon {
  position: absolute;
  left: 0.75rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #64748b;
  pointer-events: none;
}

.input-with-addon {
  padding-left: 2.75rem !important;
}

/* Product Photo Upload */
.upload-area {
  width: 100%;
}

.file-input-hidden {
  display: none;
}

.upload-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.85rem 1rem;
  border: 1.5px dashed #cbd5e1;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  transition: all 0.15s ease;
}

.upload-card:hover {
  border-color: #94a3b8;
  background: #f1f5f9;
}

.upload-card.uploading {
  opacity: 0.7;
  cursor: wait;
}

.upload-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  flex-shrink: 0;
}

.upload-content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.upload-title {
  font-size: 0.825rem;
  font-weight: 600;
  color: #0f172a;
}

.upload-note {
  font-size: 0.72rem;
  color: #64748b;
}

.image-preview-box {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.65rem 0.85rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.preview-thumb {
  width: 48px;
  height: 48px;
  border-radius: 6px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  flex-shrink: 0;
}

.preview-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  flex: 1;
}

.preview-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: #0f172a;
}

.preview-desc {
  font-size: 0.72rem;
  color: #64748b;
}

.preview-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-text {
  font-size: 0.75rem;
  font-weight: 500;
  color: #0f172a;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}

.btn-text:hover {
  text-decoration: underline;
}

.btn-text.danger {
  color: #dc2626;
}

.btn-text.danger:hover {
  color: #b91c1c;
}

.action-divider {
  color: #cbd5e1;
  font-size: 0.75rem;
}

/* Settings Panel & Checkboxes */
.settings-panel {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  padding: 0.75rem 0.9rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.custom-check-item {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  cursor: pointer;
}

.native-check {
  width: 15px;
  height: 15px;
  accent-color: #0f172a;
  margin-top: 2px;
  cursor: pointer;
}

.check-text-group {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
}

.check-title {
  font-size: 0.8rem;
  font-weight: 600;
  color: #1e293b;
}

.check-desc {
  font-size: 0.715rem;
  color: #64748b;
}

/* Modal Footer */
.modal-foot {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.65rem;
  padding: 0.9rem 1.4rem;
  border-top: 1px solid #f1f5f9;
  background: #fafafa;
  flex-shrink: 0;
}

.spinner-button {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: inline-block;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(15, 23, 42, 0.2);
  border-top-color: #0f172a;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Modal Error Alert */
.modal-error-alert {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.75rem 0.95rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  font-size: 0.8rem;
  font-weight: 500;
  border-radius: 8px;
  line-height: 1.4;
}

.error-alert-icon {
  flex-shrink: 0;
  color: #dc2626;
}

.error-alert-text {
  flex: 1;
}

.error-alert-dismiss {
  background: none;
  border: none;
  color: #991b1b;
  cursor: pointer;
  padding: 0.15rem 0.35rem;
  font-size: 0.75rem;
  line-height: 1;
  border-radius: 4px;
  opacity: 0.7;
  transition: opacity 0.15s;
}

.error-alert-dismiss:hover {
  opacity: 1;
  background: rgba(153, 27, 27, 0.08);
}

/* Header & Banner Customer Links */
.btn-customer-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: #059669;
  color: #ffffff;
  border: 1px solid #059669;
  font-weight: 600;
  text-decoration: none;
}

.btn-customer-link:hover {
  background: #047857;
  color: #ffffff;
}

.banner-customer-link {
  margin-left: 0.85rem;
  font-weight: 600;
  color: #047857;
  text-decoration: underline;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.banner-customer-link:hover {
  color: #065f46;
}

/* Success Confirmation Modal Box */
.success-modal-box {
  max-width: 480px;
  text-align: center;
  padding: 1.75rem 1.5rem;
  border-radius: 12px;
}

.success-modal-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  margin-bottom: 1.25rem;
}

.success-check-badge {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.35rem;
}

.success-modal-header h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.01em;
}

.success-modal-header p {
  font-size: 0.825rem;
  color: #64748b;
  margin: 0;
  max-width: 360px;
}

.success-product-card {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  text-align: left;
  margin-bottom: 1.25rem;
}

.success-prod-img {
  width: 58px;
  height: 58px;
  border-radius: 6px;
  object-fit: cover;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  flex-shrink: 0;
}

.success-prod-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.success-key-badge {
  font-size: 0.7rem;
  font-weight: 600;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  color: #64748b;
}

.success-prod-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.success-prod-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.meta-tag {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  color: #334155;
}

.meta-tag.meta-price {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.meta-tag.meta-stock {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.meta-tag.status-active {
  background: #ecfdf5;
  border-color: #a7f3d0;
  color: #047857;
}

.success-modal-actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.btn-add-another {
  width: 100%;
  padding: 0.7rem 1.25rem;
  font-size: 0.9rem;
  font-weight: 600;
  justify-content: center;
  background: #0f172a;
  color: #ffffff;
  border-radius: 7px;
}

.btn-add-another:hover {
  background: #1e293b;
}

.success-secondary-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem;
}

.success-secondary-actions .btn {
  justify-content: center;
  padding: 0.55rem 0.85rem;
}

.btn-customer-cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #059669;
  color: #ffffff;
  font-weight: 600;
  padding: 0.55rem 0.85rem;
  border-radius: 6px;
  text-decoration: none;
  font-size: 0.85rem;
  transition: background 0.15s ease;
}

.btn-customer-cta:hover {
  background: #047857;
  color: #ffffff;
}
</style>
