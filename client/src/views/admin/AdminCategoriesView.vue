<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../services/api';

const categories = ref([]);
const isLoading = ref(true);
const actionMessage = ref('');
const errorMessage = ref('');

// Search
const searchQuery = ref('');

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteConfirmModal = ref(false);
const categoryToDelete = ref(null);
const isSubmitting = ref(false);

// Category Form
const categoryForm = ref({
  id: null,
  name: '',
  description: '',
  icon: '🛒',
  is_active: true,
});

const emojiPresets = [
  { emoji: '🥦', label: 'Vegetables' },
  { emoji: '🍎', label: 'Fruits' },
  { emoji: '🥛', label: 'Dairy' },
  { emoji: '🥖', label: 'Bakery' },
  { emoji: '🥩', label: 'Meat' },
  { emoji: '🌾', label: 'Pantry' },
  { emoji: '🍫', label: 'Snacks' },
  { emoji: '☕', label: 'Drinks' },
  { emoji: '🧼', label: 'Household' },
  { emoji: '🧴', label: 'Personal' },
  { emoji: '🧀', label: 'Cheese' },
  { emoji: '🛒', label: 'General' },
];

async function loadData() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const res = await api.get('/admin/categories');
    categories.value = res.data.categories;
  } catch (err) {
    console.error('Failed to load categories:', err);
    errorMessage.value = 'Could not load categories from server.';
  } finally {
    isLoading.value = false;
  }
}

const filteredCategories = computed(() => {
  return categories.value.filter((c) => {
    return (c.name || '').toLowerCase().includes(searchQuery.value.toLowerCase()) ||
           (c.description || '').toLowerCase().includes(searchQuery.value.toLowerCase());
  });
});

function openCreateModal() {
  resetForm();
  showCreateModal.value = true;
}

function openEditModal(category) {
  categoryForm.value = {
    id: category.id,
    name: category.name,
    description: category.description || '',
    icon: category.icon || category.icon_url || '🛒',
    is_active: !!category.is_active,
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

async function handleCreateCategory() {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    const payload = {
      name: categoryForm.value.name.trim(),
      description: categoryForm.value.description ? categoryForm.value.description.trim() : null,
      icon: categoryForm.value.icon || '🛒',
      icon_url: categoryForm.value.icon || '🛒',
      is_active: categoryForm.value.is_active !== false,
    };
    await api.post('/admin/categories', payload);
    actionMessage.value = `✓ Category "${payload.name}" created successfully!`;
    showCreateModal.value = false;
    resetForm();
    await loadData();
    broadcastCatalogUpdate('category_created');
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to create category.';
  } finally {
    isSubmitting.value = false;
  }
}

async function handleUpdateCategory() {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    const payload = {
      name: categoryForm.value.name.trim(),
      description: categoryForm.value.description ? categoryForm.value.description.trim() : null,
      icon: categoryForm.value.icon || '🛒',
      icon_url: categoryForm.value.icon || '🛒',
      is_active: categoryForm.value.is_active !== false,
    };
    await api.put(`/admin/categories/${categoryForm.value.id}`, payload);
    actionMessage.value = `✓ Category "${payload.name}" updated successfully!`;
    showEditModal.value = false;
    resetForm();
    await loadData();
    broadcastCatalogUpdate('category_updated');
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update category.';
  } finally {
    isSubmitting.value = false;
  }
}

function confirmDelete(category) {
  categoryToDelete.value = category;
  showDeleteConfirmModal.value = true;
}

async function executeDelete() {
  if (!categoryToDelete.value) return;
  isSubmitting.value = true;
  errorMessage.value = '';
  try {
    await api.delete(`/admin/categories/${categoryToDelete.value.id}`);
    actionMessage.value = `✓ Category '${categoryToDelete.value.name}' deleted successfully.`;
    showDeleteConfirmModal.value = false;
    categoryToDelete.value = null;
    await loadData();
    broadcastCatalogUpdate('category_deleted');
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to delete category.';
  } finally {
    isSubmitting.value = false;
  }
}

function resetForm() {
  categoryForm.value = {
    id: null,
    name: '',
    description: '',
    icon: '🛒',
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
        <h2>Product Categories</h2>
        <p>Organize products into categories for easier browsing</p>
      </div>

      <button class="btn btn-primary" @click="openCreateModal">
        + Create New Category
      </button>
    </div>

    <!-- Success / Error Banner -->
    <div v-if="actionMessage" class="banner success-banner">
      ✓ {{ actionMessage }}
    </div>
    <div v-if="errorMessage" class="banner error-banner">
      ⚠ {{ errorMessage }}
    </div>

    <!-- Search -->
    <div class="filter-bar">
      <div class="search-wrap">
        <span class="search-icon">🔍</span>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Search categories..."
          class="search-input"
        />
      </div>
    </div>

    <!-- Categories Table -->
    <div class="table-card">
      <table class="data-table">
        <thead>
          <tr>
            <th>Category</th>
            <th>Description</th>
            <th>Products</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="isLoading">
            <td colspan="5" class="text-center py-6 text-muted">Loading categories...</td>
          </tr>
          <tr v-else-if="filteredCategories.length === 0">
            <td colspan="5" class="text-center py-6 text-muted">
              No categories found matching your search.
            </td>
          </tr>
          <tr v-for="category in filteredCategories" :key="category.id">
            <td>
              <div class="category-cell">
                <div class="category-icon">
                  {{ category.icon || category.icon_url || '📦' }}
                </div>
                <strong class="category-name">{{ category.name }}</strong>
              </div>
            </td>
            <td>
              <span class="category-desc">{{ category.description || '-' }}</span>
            </td>
            <td>
              <span class="product-count">{{ category.products_count }} products</span>
            </td>
            <td>
              <span
                class="status-chip"
                :class="category.is_active ? 'chip-green' : 'chip-gray'"
              >
                {{ category.is_active ? '✓ Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-right">
              <div class="action-btn-group">
                <button
                  class="btn btn-sm btn-outline"
                  @click="openEditModal(category)"
                  title="Edit Category"
                >
                  ✏️ Edit
                </button>

                <button
                  class="btn btn-sm btn-danger"
                  @click="confirmDelete(category)"
                  title="Delete Category"
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
            <h3>Create New Category</h3>
            <p>Add a new category to organize products</p>
          </div>
          <button class="close-btn" @click="showCreateModal = false">×</button>
        </div>

        <form @submit.prevent="handleCreateCategory" class="modal-form">
          <div class="form-group">
            <label>Category Name *</label>
            <input
              type="text"
              v-model="categoryForm.name"
              required
              placeholder="e.g. Fresh Vegetables"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea
              v-model="categoryForm.description"
              placeholder="Brief description of this category"
              class="form-input"
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Icon / Emoji</label>
            <div class="icon-input-row">
              <input
                type="text"
                v-model="categoryForm.icon"
                placeholder="e.g. 🥬 or 🍎"
                class="form-input icon-input"
              />
              <span class="icon-preview-box">{{ categoryForm.icon || '🛒' }}</span>
            </div>
            <div class="emoji-presets-list">
              <span class="presets-label">Quick Pick:</span>
              <button
                type="button"
                v-for="p in emojiPresets"
                :key="p.emoji"
                class="emoji-preset-btn"
                :class="{ active: categoryForm.icon === p.emoji }"
                @click="categoryForm.icon = p.emoji"
                :title="p.label"
              >
                {{ p.emoji }} {{ p.label }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="categoryForm.is_active" />
              <span>Category is active and visible</span>
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
              {{ isSubmitting ? 'Creating...' : 'Create Category' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- EDIT MODAL -->
    <div v-if="showEditModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <div>
            <h3>Edit Category</h3>
            <p>Update category information</p>
          </div>
          <button class="close-btn" @click="showEditModal = false">×</button>
        </div>

        <form @submit.prevent="handleUpdateCategory" class="modal-form">
          <div class="form-group">
            <label>Category Name *</label>
            <input
              type="text"
              v-model="categoryForm.name"
              required
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea
              v-model="categoryForm.description"
              class="form-input"
              rows="3"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Icon / Emoji</label>
            <div class="icon-input-row">
              <input
                type="text"
                v-model="categoryForm.icon"
                placeholder="e.g. 🥬 or 🍎"
                class="form-input icon-input"
              />
              <span class="icon-preview-box">{{ categoryForm.icon || '🛒' }}</span>
            </div>
            <div class="emoji-presets-list">
              <span class="presets-label">Quick Pick:</span>
              <button
                type="button"
                v-for="p in emojiPresets"
                :key="p.emoji"
                class="emoji-preset-btn"
                :class="{ active: categoryForm.icon === p.emoji }"
                @click="categoryForm.icon = p.emoji"
                :title="p.label"
              >
                {{ p.emoji }} {{ p.label }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="categoryForm.is_active" />
              <span>Category is active and visible</span>
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
              {{ isSubmitting ? 'Updating...' : 'Update Category' }}
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
          <p>Are you sure you want to delete <strong>{{ categoryToDelete?.name }}</strong>?</p>
          <p class="text-muted">Categories containing products cannot be deleted.</p>
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
            {{ isSubmitting ? 'Deleting...' : 'Delete Category' }}
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
  margin-bottom: 1.5rem;
}

.search-wrap {
  max-width: 400px;
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

.category-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.category-icon {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.category-name {
  color: #0f172a;
  font-weight: 500;
}

.category-desc {
  color: #64748b;
  font-size: 0.875rem;
}

.product-count {
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

.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #64748b;
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

.icon-input-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.icon-input {
  flex: 1;
}

.icon-preview-box {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background: #f1f5f9;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.emoji-presets-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.6rem;
  align-items: center;
}

.presets-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  margin-right: 0.25rem;
}

.emoji-preset-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  cursor: pointer;
  color: #334155;
  transition: all 0.15s ease;
}

.emoji-preset-btn:hover {
  background: #e2e8f0;
  border-color: #cbd5e1;
}

.emoji-preset-btn.active {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}
</style>
