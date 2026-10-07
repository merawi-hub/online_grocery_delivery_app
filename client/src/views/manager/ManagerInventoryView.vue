<script setup>
import { ref, onMounted, computed, reactive, inject } from 'vue';
import api from '../../services/api';

const sidebarState = inject('sidebarState', null);

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

// New Product Form
const newProduct = reactive({
  name: '',
  category_id: 1,
  sku: '',
  pricing_type: 'fixed_unit',
  unit_of_measure: 'piece',
  step_weight_grams: 500,
  price: 50.00,
  discount_price: null,
  stock_quantity: 25,
  is_perishable: false,
  is_in_stock: true,
  image_url: 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=600&q=80',
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
});

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

// Add Product Handler
async function handleCreateProduct() {
  isSubmitting.value = true;
  try {
    await api.post('/manager/products', {
      name: newProduct.name,
      category_id: newProduct.category_id,
      sku: newProduct.sku || undefined,
      pricing_type: newProduct.pricing_type,
      unit_of_measure: newProduct.unit_of_measure,
      step_weight_grams: newProduct.pricing_type === 'weighted' ? newProduct.step_weight_grams : null,
      price: newProduct.price,
      discount_price: newProduct.discount_price || null,
      stock_quantity: newProduct.stock_quantity,
      is_perishable: newProduct.is_perishable,
      is_in_stock: newProduct.is_in_stock,
      image_url: newProduct.image_url,
    });

    showFeedback('Product added successfully!', 'success');
    isAddModalOpen.value = false;
    resetNewProduct();
    await loadData();
  } catch (err) {
    showFeedback(err.response?.data?.message || 'Failed to add product.', 'error');
  } finally {
    isSubmitting.value = false;
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
  isEditModalOpen.value = true;
}

// Submit Edit Product
async function handleUpdateProduct() {
  isSubmitting.value = true;
  try {
    await api.put(`/manager/products/${editForm.id}`, {
      name: editForm.name,
      category_id: editForm.category_id,
      pricing_type: editForm.pricing_type,
      unit_of_measure: editForm.unit_of_measure,
      price: editForm.price,
      discount_price: editForm.discount_price || null,
      stock_quantity: editForm.stock_quantity,
      is_in_stock: editForm.is_in_stock,
      is_perishable: editForm.is_perishable,
    });

    showFeedback(`Updated ${editForm.name}`, 'success');
    isEditModalOpen.value = false;
    await loadData();
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
    showFeedback(`Removed ${item.name}`, 'success');
    await loadData();
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
  newProduct.sku = '';
  newProduct.price = 50.00;
  newProduct.discount_price = null;
  newProduct.stock_quantity = 25;
}

function selectPresetImage(url) {
  newProduct.image_url = url;
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
        <button
          v-if="sidebarState"
          type="button"
          class="btn btn-outline space-btn"
          @click="sidebarState.toggle"
          :title="sidebarState.isMinimized.value ? 'Restore sidebar width' : 'Minimize nav bar for maximum table space'"
        >
          <span>{{ sidebarState.isMinimized.value ? '⇥ Restore Nav' : '⇤ Maximize Table Space' }}</span>
        </button>

        <button class="btn btn-primary" @click="isAddModalOpen = true">
          + Add Product
        </button>
      </div>
    </div>

    <!-- Feedback Message -->
    <div v-if="feedbackMsg" class="feedback-banner" :class="feedbackType">
      {{ feedbackMsg }}
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="toolbar">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search products or SKU..."
        />
        <button v-if="searchQuery" class="clear-btn" @click="searchQuery = ''">✕</button>
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
                <img :src="item.image_url" :alt="item.name" class="product-img" />
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
      <div class="modal-sheet modal-box">
        <div class="modal-head">
          <div>
            <h3>Add New Product</h3>
            <p>Register an item and publish it to the branch catalog.</p>
          </div>
          <button class="close-btn" @click="isAddModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="handleCreateProduct" class="modal-body">
          <div class="form-row">
            <div class="form-group flex-2">
              <label>Product Name *</label>
              <input type="text" v-model="newProduct.name" required placeholder="e.g. Fresh Red Apples" class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label>Category Aisle *</label>
              <select v-model="newProduct.category_id" required class="form-input">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>SKU (Optional)</label>
              <input type="text" v-model="newProduct.sku" placeholder="Auto-generated if blank" class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label>Pricing Rule *</label>
              <select v-model="newProduct.pricing_type" class="form-input">
                <option value="fixed_unit">Fixed Unit (Piece / Pack)</option>
                <option value="weighted">Weighted Produce (kg)</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Unit of Measure *</label>
              <select v-model="newProduct.unit_of_measure" class="form-input">
                <option value="piece">piece</option>
                <option value="kg">kg</option>
                <option value="pack">pack</option>
                <option value="bottle">bottle</option>
                <option value="tray">tray</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Retail Price (ETB) *</label>
              <input type="number" step="0.5" min="0.01" v-model.number="newProduct.price" required class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label>Stock Quantity *</label>
              <input type="number" step="1" min="0" v-model.number="newProduct.stock_quantity" required class="form-input" />
            </div>

            <div class="form-group flex-1" v-if="newProduct.pricing_type === 'weighted'">
              <label>Scale Step (Grams)</label>
              <input type="number" step="50" v-model.number="newProduct.step_weight_grams" class="form-input" />
            </div>
          </div>

          <div class="form-group">
            <label>Product Image URL</label>
            <input type="url" v-model="newProduct.image_url" placeholder="https://..." class="form-input" />
            <div class="image-presets">
              <small>Presets:</small>
              <button type="button" class="preset-btn" @click="selectPresetImage('https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&w=600&q=80')">Bananas</button>
              <button type="button" class="preset-btn" @click="selectPresetImage('https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?auto=format&fit=crop&w=600&q=80')">Apples</button>
              <button type="button" class="preset-btn" @click="selectPresetImage('https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&w=600&q=80')">Milk</button>
              <button type="button" class="preset-btn" @click="selectPresetImage('https://images.unsplash.com/photo-1604503468506-a8da13d82791?auto=format&fit=crop&w=600&q=80')">Meat</button>
            </div>
          </div>

          <div class="checkbox-row">
            <label class="checkbox-label">
              <input type="checkbox" v-model="newProduct.is_perishable" />
              <span>Perishable item (Requires cool handling)</span>
            </label>
            <label class="checkbox-label">
              <input type="checkbox" v-model="newProduct.is_in_stock" />
              <span>Make immediately available in store</span>
            </label>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="isAddModalOpen = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? 'Creating...' : 'Save Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: EDIT PRODUCT -->
    <div v-if="isEditModalOpen" class="modal-overlay" @click.self="isEditModalOpen = false">
      <div class="modal-sheet modal-box">
        <div class="modal-head">
          <div>
            <h3>Edit Product</h3>
            <p>Update product specifications, price, or stock count.</p>
          </div>
          <button class="close-btn" @click="isEditModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="handleUpdateProduct" class="modal-body">
          <div class="form-group">
            <label>Product Name</label>
            <input type="text" v-model="editForm.name" required class="form-input" />
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Category</label>
              <select v-model="editForm.category_id" class="form-input">
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Pricing Type</label>
              <select v-model="editForm.pricing_type" class="form-input">
                <option value="fixed_unit">Fixed Unit</option>
                <option value="weighted">Weighted (kg)</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Price (ETB)</label>
              <input type="number" step="0.5" v-model.number="editForm.price" required class="form-input" />
            </div>

            <div class="form-group flex-1">
              <label>Stock Quantity</label>
              <input type="number" step="0.5" v-model.number="editForm.stock_quantity" required class="form-input" />
            </div>
          </div>

          <div class="checkbox-row">
            <label class="checkbox-label">
              <input type="checkbox" v-model="editForm.is_in_stock" />
              <span>In Stock</span>
            </label>
          </div>

          <div class="modal-foot">
            <button type="button" class="btn btn-outline" @click="isEditModalOpen = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
              {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
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

.space-btn {
  font-size: 0.78rem;
  background: #ffffff;
}

.space-btn:hover {
  background: #f9fafb;
  border-color: #111827;
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
  max-width: 640px;
}

.modal-head {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-head h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #111827;
}

.modal-head p {
  font-size: 0.75rem;
  color: #6b7280;
}

.close-btn {
  font-size: 1rem;
  color: #9ca3af;
  cursor: pointer;
}

.close-btn:hover {
  color: #111827;
}

.modal-body {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
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
  gap: 0.25rem;
}

.form-group label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
}

.image-presets {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  margin-top: 0.25rem;
  flex-wrap: wrap;
}

.image-presets small {
  font-size: 0.7rem;
  color: #6b7280;
}

.preset-btn {
  font-size: 0.7rem;
  padding: 0.15rem 0.45rem;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  background: #f9fafb;
  color: #374151;
  cursor: pointer;
}

.preset-btn:hover {
  border-color: #111827;
  color: #111827;
}

.checkbox-row {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  padding-top: 0.25rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.78rem;
  color: #374151;
  cursor: pointer;
}

.modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
}
</style>
