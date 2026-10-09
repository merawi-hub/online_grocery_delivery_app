<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';

const router = useRouter();
const authStore = useAuthStore();

// Navigation & Active Tab
const activeTab = ref('managers'); // 'managers' | 'deliverers' | 'overview'

// Data States
const metrics = ref({
  stock_managers: { total: 0, approved: 0, pending: 0 },
  deliverers: { total: 0, approved: 0, pending: 0 },
  customers: { total: 0 },
});

const stockManagers = ref([]);
const deliverers = ref([]);
const isLoading = ref(true);
const actionMessage = ref('');
const errorMessage = ref('');

// Filter & Search
const managerSearch = ref('');
const managerFilterStatus = ref('all'); // 'all' | 'approved' | 'pending'

const delivererSearch = ref('');
const delivererFilterStatus = ref('all');

// Modals
const showCreateManagerModal = ref(false);
const showCreateDelivererModal = ref(false);
const showDeleteConfirmModal = ref(false);
const userToDelete = ref(null);
const isSubmitting = ref(false);

// New Stock Manager Form (User requirement: F.Name, L.Name, password, phone number)
const managerForm = ref({
  first_name: '',
  last_name: '',
  phone_number: '',
  password: '',
  email: '',
  approval_status: 'approved',
});

// New Deliverer Form
const delivererForm = ref({
  first_name: '',
  last_name: '',
  phone_number: '',
  password: '',
  vehicle_type: 'Motorbike',
  approval_status: 'approved',
});

async function loadData() {
  isLoading.value = true;
  errorMessage.value = '';
  try {
    const [overviewRes, managersRes, deliverersRes] = await Promise.all([
      api.get('/admin/overview'),
      api.get('/admin/stock-managers'),
      api.get('/admin/deliverers'),
    ]);

    metrics.value = overviewRes.data.metrics;
    stockManagers.value = managersRes.data.stock_managers;
    deliverers.value = deliverersRes.data.deliverers;
  } catch (err) {
    console.error('Failed to load admin console data:', err);
    errorMessage.value = 'Could not load data from server. Please check backend connection.';
  } finally {
    isLoading.value = false;
  }
}

// Filtered Stock Managers
const filteredStockManagers = computed(() => {
  return stockManagers.value.filter((m) => {
    const matchSearch =
      (m.name || '').toLowerCase().includes(managerSearch.value.toLowerCase()) ||
      (m.phone_number || '').includes(managerSearch.value);
    const matchStatus =
      managerFilterStatus.value === 'all' || m.approval_status === managerFilterStatus.value;
    return matchSearch && matchStatus;
  });
});

// Filtered Deliverers
const filteredDeliverers = computed(() => {
  return deliverers.value.filter((d) => {
    const matchSearch =
      (d.name || '').toLowerCase().includes(delivererSearch.value.toLowerCase()) ||
      (d.phone_number || '').includes(delivererSearch.value);
    const matchStatus =
      delivererFilterStatus.value === 'all' || d.approval_status === delivererFilterStatus.value;
    return matchSearch && matchStatus;
  });
});

// Create Stock Manager
async function handleCreateStockManager() {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    const res = await api.post('/admin/stock-managers', {
      first_name: managerForm.value.first_name,
      last_name: managerForm.value.last_name,
      phone_number: managerForm.value.phone_number,
      password: managerForm.value.password,
      email: managerForm.value.email || null,
      approval_status: managerForm.value.approval_status,
    });

    actionMessage.value = `Stock Manager '${res.data.stock_manager.name}' created successfully!`;
    showCreateManagerModal.value = false;
    resetManagerForm();
    await loadData();
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to create stock manager.';
  } finally {
    isSubmitting.value = false;
  }
}

// Create Deliverer
async function handleCreateDeliverer() {
  errorMessage.value = '';
  isSubmitting.value = true;
  try {
    const res = await api.post('/admin/deliverers', {
      first_name: delivererForm.value.first_name,
      last_name: delivererForm.value.last_name,
      phone_number: delivererForm.value.phone_number,
      password: delivererForm.value.password,
      vehicle_type: delivererForm.value.vehicle_type,
      approval_status: delivererForm.value.approval_status,
    });

    actionMessage.value = `Deliverer '${res.data.deliverer.name}' created successfully!`;
    showCreateDelivererModal.value = false;
    resetDelivererForm();
    await loadData();
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to create deliverer.';
  } finally {
    isSubmitting.value = false;
  }
}

// Toggle Approval Status
async function toggleManagerApproval(manager) {
  const newStatus = manager.approval_status === 'approved' ? 'pending' : 'approved';
  try {
    await api.patch(`/admin/stock-managers/${manager.id}/approval`, {
      approval_status: newStatus,
    });
    manager.approval_status = newStatus;
    actionMessage.value = `Updated '${manager.name}' status to ${newStatus}.`;
    await loadData();
    setTimeout(() => (actionMessage.value = ''), 3000);
  } catch (err) {
    alert('Failed to update approval status.');
  }
}

async function toggleDelivererApproval(deliverer) {
  const newStatus = deliverer.approval_status === 'approved' ? 'pending' : 'approved';
  try {
    await api.patch(`/admin/deliverers/${deliverer.id}/approval`, {
      approval_status: newStatus,
    });
    deliverer.approval_status = newStatus;
    actionMessage.value = `Updated '${deliverer.name}' status to ${newStatus}.`;
    await loadData();
    setTimeout(() => (actionMessage.value = ''), 3000);
  } catch (err) {
    alert('Failed to update approval status.');
  }
}

// Open Delete Confirm Modal (Requirement: "remove existed managers")
function confirmRemove(user, type) {
  userToDelete.value = { ...user, type };
  showDeleteConfirmModal.value = true;
}

async function executeRemoval() {
  if (!userToDelete.value) return;
  isSubmitting.value = true;
  try {
    if (userToDelete.value.type === 'manager') {
      await api.delete(`/admin/stock-managers/${userToDelete.value.id}`);
      actionMessage.value = `Stock Manager '${userToDelete.value.name}' removed from system.`;
    } else {
      await api.delete(`/admin/deliverers/${userToDelete.value.id}`);
      actionMessage.value = `Deliverer '${userToDelete.value.name}' removed from system.`;
    }
    showDeleteConfirmModal.value = false;
    userToDelete.value = null;
    await loadData();
    setTimeout(() => (actionMessage.value = ''), 4000);
  } catch (err) {
    alert('Failed to remove user.');
  } finally {
    isSubmitting.value = false;
  }
}

function resetManagerForm() {
  managerForm.value = {
    first_name: '',
    last_name: '',
    phone_number: '',
    password: '',
    email: '',
    approval_status: 'approved',
  };
}

function resetDelivererForm() {
  delivererForm.value = {
    first_name: '',
    last_name: '',
    phone_number: '',
    password: '',
    vehicle_type: 'Motorbike',
    approval_status: 'approved',
  };
}

function handleLogout() {
  authStore.logout();
  router.push('/login');
}

onMounted(() => {
  // If not logged in as admin, log in with default credentials for instant testing
  if (!authStore.isAuthenticated || authStore.userRole !== 'admin') {
    authStore.login('admin', 'admin123').then(() => {
      loadData();
    });
  } else {
    loadData();
  }
});
</script>

<template>
  <div class="admin-wrapper">
    <!-- Top Bar Navigation -->
    <header class="admin-header">
      <div class="header-left">
        <div class="brand-group">
          <span class="brand-badge">👑 4th Stakeholder Hub</span>
          <h1 class="brand-title">General Operations Manager Console</h1>
        </div>
        <p class="brand-desc">Oversees all stock managers, physical branches, and deliverer courier fleet.</p>
      </div>

      <div class="header-right">
        <div class="admin-profile-pill">
          <div class="admin-avatar">GM</div>
          <div class="admin-meta">
            <strong>admin</strong>
            <small>General Operations Manager • No mobile required</small>
          </div>
        </div>

        <button class="btn btn-outline" @click="loadData" :disabled="isLoading">
          🔄 Refresh
        </button>

        <button class="btn btn-danger-outline" @click="handleLogout">
          Sign Out
        </button>
      </div>
    </header>

    <!-- Success / Error Banner -->
    <div v-if="actionMessage" class="banner success-banner">
      ✓ {{ actionMessage }}
    </div>
    <div v-if="errorMessage" class="banner error-banner">
      ⚠ {{ errorMessage }}
    </div>

    <!-- Metrics Cards Row -->
    <section class="metrics-row">
      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Stock Managers Under Stock</span>
          <span class="metric-icon">🏬</span>
        </div>
        <div class="metric-num">{{ metrics.stock_managers.total }}</div>
        <div class="metric-breakdown">
          <span class="pill-green">{{ metrics.stock_managers.approved }} Approved</span>
          <span class="pill-amber">{{ metrics.stock_managers.pending }} Pending</span>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Courier Fleet Couriers</span>
          <span class="metric-icon">🛵</span>
        </div>
        <div class="metric-num">{{ metrics.deliverers.total }}</div>
        <div class="metric-breakdown">
          <span class="pill-green">{{ metrics.deliverers.approved }} Approved</span>
          <span class="pill-amber">{{ metrics.deliverers.pending }} Pending</span>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-top">
          <span class="metric-label">Registered Customers</span>
          <span class="metric-icon">👥</span>
        </div>
        <div class="metric-num">{{ metrics.customers.total }}</div>
        <div class="metric-breakdown">
          <span class="pill-blue">Active Customer Base</span>
        </div>
      </div>

      <div class="metric-card highlight">
        <div class="metric-top">
          <span class="metric-label">Operational Authority</span>
          <span class="metric-icon">🛡️</span>
        </div>
        <div class="authority-text">
          <strong>Full Clearance</strong>
          <small>Create & approve managers, assign courier fleet, remove accounts.</small>
        </div>
      </div>
    </section>

    <!-- Navigation Tabs -->
    <div class="tabs-bar">
      <button
        class="tab-link"
        :class="{ active: activeTab === 'managers' }"
        @click="activeTab = 'managers'"
      >
        🏬 Stock Managers Under Stock ({{ metrics.stock_managers.total }})
      </button>

      <button
        class="tab-link"
        :class="{ active: activeTab === 'deliverers' }"
        @click="activeTab = 'deliverers'"
      >
        🛵 Delivery Couriers ({{ metrics.deliverers.total }})
      </button>

      <button
        class="tab-link"
        :class="{ active: activeTab === 'switcher' }"
        @click="activeTab = 'switcher'"
      >
        ⚡ Quick Portal Switcher
      </button>
    </div>

    <!-- TAB 1: STOCK MANAGERS MANAGEMENT -->
    <section v-if="activeTab === 'managers'" class="tab-panel">
      <div class="panel-header">
        <div>
          <h2>Stock Managers</h2>
          <p>Create and approve store stock managers who manage stock and sales, or remove existed managers.</p>
        </div>

        <button class="btn btn-primary" @click="showCreateManagerModal = true">
          + Register New Stock Manager
        </button>
      </div>

      <!-- Search & Filters -->
      <div class="filter-bar">
        <div class="search-wrap">
          <span class="search-icon">🔍</span>
          <input
            type="text"
            v-model="managerSearch"
            placeholder="Search stock manager by name or phone..."
            class="search-input"
          />
        </div>

        <div class="filter-group">
          <label>Status Filter:</label>
          <select v-model="managerFilterStatus" class="filter-select">
            <option value="all">All Managers</option>
            <option value="approved">Approved Only</option>
            <option value="pending">Pending Approval</option>
          </select>
        </div>
      </div>

      <!-- Managers Table -->
      <div class="table-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Stock Manager</th>
              <th>Phone Number</th>
              <th>Approval Status</th>
              <th>Role Authority</th>
              <th>Date Added</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="6" class="text-center py-6 text-muted">Loading stock managers...</td>
            </tr>
            <tr v-else-if="filteredStockManagers.length === 0">
              <td colspan="6" class="text-center py-6 text-muted">
                No stock managers match your search criteria.
              </td>
            </tr>
            <tr v-for="manager in filteredStockManagers" :key="manager.id">
              <td>
                <div class="user-cell">
                  <div class="cell-avatar">
                    {{ (manager.first_name || manager.name || 'M').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <strong class="user-name">{{ manager.name }}</strong>
                    <small class="user-sub" v-if="manager.first_name && manager.last_name">
                      F: {{ manager.first_name }} • L: {{ manager.last_name }}
                    </small>
                    <small class="user-email" v-if="manager.email">{{ manager.email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="phone-badge">📞 {{ manager.phone_number }}</span>
              </td>
              <td>
                <span
                  class="status-chip"
                  :class="manager.approval_status === 'approved' ? 'chip-green' : 'chip-amber'"
                >
                  {{ manager.approval_status === 'approved' ? '✓ Approved' : '⏳ Pending Approval' }}
                </span>
              </td>
              <td>
                <span class="role-pill">Stock & Sales Manager</span>
              </td>
              <td>
                <span class="date-text">{{ new Date(manager.created_at).toLocaleDateString() }}</span>
              </td>
              <td class="text-right">
                <div class="action-btn-group">
                  <!-- Approve / Reject Toggle -->
                  <button
                    class="btn btn-sm"
                    :class="manager.approval_status === 'approved' ? 'btn-warn' : 'btn-success'"
                    @click="toggleManagerApproval(manager)"
                    :title="manager.approval_status === 'approved' ? 'Set to Pending' : 'Approve Manager'"
                  >
                    {{ manager.approval_status === 'approved' ? 'Set Pending' : '✓ Approve' }}
                  </button>

                  <!-- Remove Existed Manager Button (User requirement) -->
                  <button
                    class="btn btn-sm btn-danger"
                    @click="confirmRemove(manager, 'manager')"
                    title="Remove Existed Manager"
                  >
                    🗑️ Remove Manager
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- TAB 2: DELIVERERS & COURIERS MANAGEMENT -->
    <section v-if="activeTab === 'deliverers'" class="tab-panel">
      <div class="panel-header">
        <div>
          <h2>Delivery Fleet Couriers</h2>
          <p>Create and approve deliverers for live order dispatch, or remove courier accounts.</p>
        </div>

        <button class="btn btn-primary" @click="showCreateDelivererModal = true">
          + Register New Deliverer
        </button>
      </div>

      <!-- Search & Filters -->
      <div class="filter-bar">
        <div class="search-wrap">
          <span class="search-icon">🔍</span>
          <input
            type="text"
            v-model="delivererSearch"
            placeholder="Search deliverer by name or phone..."
            class="search-input"
          />
        </div>

        <div class="filter-group">
          <label>Status Filter:</label>
          <select v-model="delivererFilterStatus" class="filter-select">
            <option value="all">All Couriers</option>
            <option value="approved">Approved Only</option>
            <option value="pending">Pending Approval</option>
          </select>
        </div>
      </div>

      <!-- Deliverers Table -->
      <div class="table-card">
        <table class="data-table">
          <thead>
            <tr>
              <th>Courier Name</th>
              <th>Phone Number</th>
              <th>Vehicle Type</th>
              <th>Approval Status</th>
              <th>Date Registered</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="6" class="text-center py-6 text-muted">Loading delivery couriers...</td>
            </tr>
            <tr v-else-if="filteredDeliverers.length === 0">
              <td colspan="6" class="text-center py-6 text-muted">
                No deliverers match your search criteria.
              </td>
            </tr>
            <tr v-for="deliverer in filteredDeliverers" :key="deliverer.id">
              <td>
                <div class="user-cell">
                  <div class="cell-avatar courier">
                    {{ (deliverer.first_name || deliverer.name || 'D').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <strong class="user-name">{{ deliverer.name }}</strong>
                    <small class="user-email" v-if="deliverer.email">{{ deliverer.email }}</small>
                  </div>
                </div>
              </td>
              <td>
                <span class="phone-badge">📞 {{ deliverer.phone_number }}</span>
              </td>
              <td>
                <span class="vehicle-pill">🛵 {{ deliverer.vehicle_type || 'Motorbike' }}</span>
              </td>
              <td>
                <span
                  class="status-chip"
                  :class="deliverer.approval_status === 'approved' ? 'chip-green' : 'chip-amber'"
                >
                  {{ deliverer.approval_status === 'approved' ? '✓ Approved' : '⏳ Pending' }}
                </span>
              </td>
              <td>
                <span class="date-text">{{ new Date(deliverer.created_at).toLocaleDateString() }}</span>
              </td>
              <td class="text-right">
                <div class="action-btn-group">
                  <button
                    class="btn btn-sm"
                    :class="deliverer.approval_status === 'approved' ? 'btn-warn' : 'btn-success'"
                    @click="toggleDelivererApproval(deliverer)"
                  >
                    {{ deliverer.approval_status === 'approved' ? 'Set Pending' : '✓ Approve' }}
                  </button>

                  <button
                    class="btn btn-sm btn-danger"
                    @click="confirmRemove(deliverer, 'deliverer')"
                  >
                    🗑️ Remove Deliverer
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- TAB 3: QUICK PORTAL SWITCHER -->
    <section v-if="activeTab === 'switcher'" class="tab-panel">
      <div class="panel-header">
        <div>
          <h2>Stakeholder Portal Jumper</h2>
          <p>Test and preview the user experience across all 4 grocery platform stakeholders.</p>
        </div>
      </div>

      <div class="stakeholder-preview-grid">
        <div class="preview-card">
          <div class="preview-badge customer">Stakeholder 1</div>
          <div class="preview-icon">🛒</div>
          <h3>Customer Portal</h3>
          <p>Customer self-registration and login. Redirects to Customer "Coming Soon" page.</p>
          <div class="preview-creds">
            <span>Default: User1 • 0969644384</span>
          </div>
          <router-link to="/customer/coming-soon" class="btn btn-primary w-full">
            Launch Customer Portal →
          </router-link>
        </div>

        <div class="preview-card">
          <div class="preview-badge stock">Stakeholder 2</div>
          <div class="preview-icon">🏬</div>
          <h3>Stock Manager Workspace</h3>
          <p>Store manager console for managing physical supermarket stock, inventory items, and orders.</p>
          <div class="preview-creds">
            <span>Default: Sara Manager • 0911000002</span>
          </div>
          <router-link to="/admin123/dashboard" class="btn btn-primary w-full">
            Launch Stock Dashboard →
          </router-link>
        </div>

        <div class="preview-card">
          <div class="preview-badge driver">Stakeholder 3</div>
          <div class="preview-icon">🛵</div>
          <h3>Deliverer / Courier Portal</h3>
          <p>Courier fleet dispatch and driver navigation. Redirects to Deliverer "Coming Soon" page.</p>
          <div class="preview-creds">
            <span>Default: Yonas Driver • 0911000004</span>
          </div>
          <router-link to="/driver/coming-soon" class="btn btn-primary w-full">
            Launch Deliverer Portal →
          </router-link>
        </div>

        <div class="preview-card current">
          <div class="preview-badge admin">Stakeholder 4 (Active)</div>
          <div class="preview-icon">👑</div>
          <h3>General Operations Manager</h3>
          <p>Full control over stock managers and deliverers. Login: admin (no mobile required).</p>
          <div class="preview-creds">
            <span>Current: admin • admin123</span>
          </div>
          <button class="btn btn-outline w-full" disabled>
            Currently Active Console
          </button>
        </div>
      </div>
    </section>

    <!-- MODAL 1: REGISTER NEW STOCK MANAGER -->
    <!-- Requirement: "the register field needed F.Name, L.Name, password, phone number." -->
    <div v-if="showCreateManagerModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <div>
            <h3>Register New Stock Manager</h3>
            <p>Fill out the required registration fields to create a store stock manager.</p>
          </div>
          <button class="close-btn" @click="showCreateManagerModal = false">×</button>
        </div>

        <form @submit.prevent="handleCreateStockManager" class="modal-form">
          <div class="form-row">
            <div class="form-group flex-1">
              <label>F.Name (First Name) *</label>
              <input
                type="text"
                v-model="managerForm.first_name"
                required
                placeholder="e.g. Sara"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>L.Name (Last Name) *</label>
              <input
                type="text"
                v-model="managerForm.last_name"
                required
                placeholder="e.g. Haile"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Phone Number *</label>
              <input
                type="text"
                v-model="managerForm.phone_number"
                required
                placeholder="0911000002"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>Password *</label>
              <input
                type="text"
                v-model="managerForm.password"
                required
                placeholder="password123"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Work Email (Optional)</label>
              <input
                type="email"
                v-model="managerForm.email"
                placeholder="manager@freshmart.et"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>Initial Approval Status</label>
              <select v-model="managerForm.approval_status" class="form-input">
                <option value="approved">Approved (Can log in immediately)</option>
                <option value="pending">Pending Approval (Needs manager review)</option>
              </select>
            </div>
          </div>

          <div class="info-note">
            💡 The stock manager will log in to the sales/stock management portal using their phone number and the password created here.
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showCreateManagerModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Registering...' : 'Register Stock Manager' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 2: REGISTER NEW DELIVERER -->
    <div v-if="showCreateDelivererModal" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <div>
            <h3>Register New Delivery Courier</h3>
            <p>Onboard a courier for order dispatch and turn-by-turn navigation.</p>
          </div>
          <button class="close-btn" @click="showCreateDelivererModal = false">×</button>
        </div>

        <form @submit.prevent="handleCreateDeliverer" class="modal-form">
          <div class="form-row">
            <div class="form-group flex-1">
              <label>F.Name (First Name) *</label>
              <input
                type="text"
                v-model="delivererForm.first_name"
                required
                placeholder="e.g. Yonas"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>L.Name (Last Name) *</label>
              <input
                type="text"
                v-model="delivererForm.last_name"
                required
                placeholder="e.g. Bekele"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Phone Number *</label>
              <input
                type="text"
                v-model="delivererForm.phone_number"
                required
                placeholder="0911000004"
                class="form-input"
              />
            </div>

            <div class="form-group flex-1">
              <label>Password *</label>
              <input
                type="text"
                v-model="delivererForm.password"
                required
                placeholder="password123"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Vehicle Type</label>
              <select v-model="delivererForm.vehicle_type" class="form-input">
                <option value="Motorbike">🛵 Motorbike (Fast Express)</option>
                <option value="Bicycle">🚲 Bicycle (Short Radius)</option>
                <option value="Car / Van">🚗 Car / Van (Bulk Orders)</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Approval Status</label>
              <select v-model="delivererForm.approval_status" class="form-input">
                <option value="approved">Approved Courier</option>
                <option value="pending">Pending Approval</option>
              </select>
            </div>
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn btn-secondary"
              @click="showCreateDelivererModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              {{ isSubmitting ? 'Registering...' : 'Register Courier' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL 3: DELETE CONFIRMATION -->
    <!-- Requirement: "remove existed managers" -->
    <div v-if="showDeleteConfirmModal" class="modal-backdrop">
      <div class="modal-box modal-sm">
        <div class="modal-header">
          <h3 class="text-danger">⚠️ Confirm Account Removal</h3>
          <button class="close-btn" @click="showDeleteConfirmModal = false">×</button>
        </div>

        <div class="modal-body-content">
          <p>
            Are you sure you want to remove <strong>{{ userToDelete?.name }}</strong>?
          </p>
          <p class="text-muted text-sm mt-2">
            This will permanently remove the {{ userToDelete?.type === 'manager' ? 'stock manager' : 'deliverer' }}
            from the system and revoke their access immediately.
          </p>
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
            @click="executeRemoval"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Removing...' : 'Confirm Remove' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-wrapper {
  min-height: 100vh;
  background: #f8fafc;
  color: #0f172a;
  padding: 1.5rem 2rem;
  font-family: var(--font-sans);
}

/* Header */
.admin-header {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem 1.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}

.brand-badge {
  display: inline-block;
  background: #fef3c7;
  color: #b45309;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.35rem;
}

.brand-title {
  font-size: 1.4rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.brand-desc {
  font-size: 0.825rem;
  color: #64748b;
  margin-top: 0.2rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.admin-profile-pill {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  background: #f1f5f9;
  padding: 0.4rem 0.85rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.admin-avatar {
  width: 32px;
  height: 32px;
  background: #0f172a;
  color: #ffffff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.78rem;
}

.admin-meta {
  display: flex;
  flex-direction: column;
}

.admin-meta strong {
  font-size: 0.85rem;
  color: #0f172a;
}

.admin-meta small {
  font-size: 0.7rem;
  color: #64748b;
}

/* Banners */
.banner {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1.25rem;
}

.success-banner {
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.error-banner {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

/* Metrics Row */
.metrics-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.metric-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
}

.metric-card.highlight {
  background: #0f172a;
  color: #ffffff;
  border-color: #0f172a;
}

.metric-card.highlight .metric-label {
  color: #94a3b8;
}

.metric-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.metric-label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.metric-icon {
  font-size: 1.35rem;
}

.metric-num {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 0.65rem;
}

.metric-breakdown {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.pill-green {
  background: #dcfce7;
  color: #166534;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
}

.pill-amber {
  background: #fef3c7;
  color: #92400e;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
}

.pill-blue {
  background: #e0f2fe;
  color: #075985;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
}

.authority-text {
  display: flex;
  flex-direction: column;
}

.authority-text strong {
  font-size: 1.1rem;
  color: #ffffff;
}

.authority-text small {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.25rem;
}

/* Tabs */
.tabs-bar {
  display: flex;
  gap: 0.5rem;
  border-bottom: 1px solid #e2e8f0;
  margin-bottom: 1.5rem;
}

.tab-link {
  padding: 0.75rem 1.25rem;
  font-size: 0.88rem;
  font-weight: 700;
  color: #64748b;
  border-bottom: 2px solid transparent;
  background: none;
  cursor: pointer;
  transition: all 0.15s ease;
}

.tab-link:hover {
  color: #0f172a;
}

.tab-link.active {
  color: #0f172a;
  border-bottom-color: #0f172a;
}

/* Panel */
.tab-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.panel-header h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.panel-header p {
  font-size: 0.825rem;
  color: #64748b;
  margin-top: 0.15rem;
}

/* Filters */
.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.search-wrap {
  flex: 1;
  max-width: 400px;
  display: flex;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 0.45rem 0.75rem;
}

.search-icon {
  margin-right: 0.5rem;
  font-size: 0.85rem;
  color: #94a3b8;
}

.search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.85rem;
  color: #0f172a;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.filter-select {
  padding: 0.45rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.825rem;
  background: #ffffff;
  color: #0f172a;
}

/* Tables */
.table-card {
  overflow-x: auto;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  font-size: 0.85rem;
}

.data-table th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  font-weight: 700;
  color: #475569;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.data-table tr:hover td {
  background: #f8fafc;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.cell-avatar {
  width: 34px;
  height: 34px;
  background: #0f172a;
  color: #ffffff;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.cell-avatar.courier {
  background: #0284c7;
}

.user-name {
  display: block;
  font-size: 0.875rem;
  color: #0f172a;
}

.user-sub {
  display: block;
  font-size: 0.72rem;
  color: #64748b;
}

.user-email {
  display: block;
  font-size: 0.72rem;
  color: #94a3b8;
}

.phone-badge {
  background: #f1f5f9;
  padding: 0.25rem 0.55rem;
  border-radius: 6px;
  font-family: monospace;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-chip {
  display: inline-block;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
}

.chip-green {
  background: #dcfce7;
  color: #166534;
}

.chip-amber {
  background: #fef3c7;
  color: #92400e;
}

.role-pill {
  background: #f1f5f9;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #334155;
  font-weight: 600;
}

.vehicle-pill {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
}

.date-text {
  font-size: 0.78rem;
  color: #64748b;
}

.action-btn-group {
  display: inline-flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

/* Stakeholder Preview Grid */
.stakeholder-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
}

.preview-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  position: relative;
}

.preview-card.current {
  border-color: #0f172a;
  background: #ffffff;
}

.preview-badge {
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  align-self: flex-start;
  margin-bottom: 0.75rem;
}

.preview-badge.customer {
  background: #dcfce7;
  color: #166534;
}

.preview-badge.stock {
  background: #fef3c7;
  color: #92400e;
}

.preview-badge.driver {
  background: #e0f2fe;
  color: #0369a1;
}

.preview-badge.admin {
  background: #0f172a;
  color: #ffffff;
}

.preview-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.preview-card h3 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 0.35rem;
}

.preview-card p {
  font-size: 0.825rem;
  color: #64748b;
  margin-bottom: 1rem;
  flex: 1;
}

.preview-creds {
  background: #ffffff;
  border: 1px dashed #cbd5e1;
  padding: 0.4rem 0.65rem;
  border-radius: 6px;
  font-size: 0.72rem;
  color: #334155;
  font-weight: 600;
  margin-bottom: 1.25rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem 1rem;
  font-size: 0.85rem;
  font-weight: 700;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 1px solid transparent;
}

.btn-primary {
  background: #0f172a;
  color: #ffffff;
}

.btn-primary:hover {
  background: #1e293b;
}

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
  border-color: #cbd5e1;
}

.btn-outline {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #334155;
}

.btn-danger-outline {
  background: #ffffff;
  border-color: #fecaca;
  color: #dc2626;
}

.btn-danger-outline:hover {
  background: #fef2f2;
}

.btn-sm {
  padding: 0.3rem 0.6rem;
  font-size: 0.75rem;
}

.btn-success {
  background: #dcfce7;
  color: #166534;
  border-color: #bbf7d0;
}

.btn-warn {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.btn-danger {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fecaca;
}

.btn-danger:hover {
  background: #ef4444;
  color: #ffffff;
}

.w-full {
  width: 100%;
}

.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

.text-muted {
  color: #64748b;
}

.text-danger {
  color: #dc2626;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.5rem;
}

.modal-box {
  background: #ffffff;
  width: 100%;
  max-width: 540px;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  padding: 2rem;
}

.modal-sm {
  max-width: 420px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 1rem;
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
}

.modal-header p {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-group label {
  font-size: 0.78rem;
  font-weight: 700;
  color: #334155;
}

.form-input {
  padding: 0.55rem 0.8rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #0f172a;
  outline: none;
}

.form-input:focus {
  border-color: #0f172a;
  box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.1);
}

.info-note {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.78rem;
  color: #475569;
  line-height: 1.4;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
</style>
