<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const driverName = computed(() => authStore.user?.name || 'Yonas Driver');
const driverPhone = computed(() => authStore.user?.phone_number || '0911000004');
const vehicleType = computed(() => authStore.user?.vehicle_type || 'Motorbike Express');
const approvalStatus = computed(() => authStore.user?.approval_status || 'approved');

function handleLogout() {
  authStore.logout();
  router.push('/login');
}
</script>

<template>
  <div class="coming-soon-wrapper">
    <!-- Top Deliverer Header Bar -->
    <header class="driver-header">
      <div class="header-container">
        <div class="brand">
          <span class="brand-logo">🛵</span>
          <div>
            <span class="brand-title">FreshMart Courier Fleet</span>
            <span class="brand-subtitle">Deliverer Portal</span>
          </div>
        </div>

        <div class="user-pill">
          <div class="avatar-circle">
            {{ driverName.charAt(0).toUpperCase() }}
          </div>
          <div class="user-info">
            <span class="user-name">{{ driverName }}</span>
            <span class="user-phone">📱 {{ driverPhone }}</span>
          </div>
          <button class="logout-btn" @click="handleLogout" title="Sign Out">
            Sign Out
          </button>
        </div>
      </div>
    </header>

    <!-- Main Hero Container -->
    <main class="content-body">
      <div class="hero-card">
        <!-- Floating Status Badge -->
        <div class="launch-badge">
          <span class="pulse-dot"></span>
          <span>COMING SOON • COURIER DISPATCH HUB</span>
        </div>

        <h1 class="hero-title">
          Smart Turn-by-Turn Dispatch <br />
          <span class="highlight-text">Fleet Navigation Launching Soon</span>
        </h1>

        <p class="hero-desc">
          Welcome, Courier <strong>{{ driverName }}</strong>.
          Your delivery driver profile has been registered in the fleet system.
          Live turn-by-turn routing and automated supermarket batch dispatch are undergoing final staging.
        </p>

        <!-- Driver Profile Summary Card -->
        <div class="account-card">
          <div class="account-card-header">
            <span class="account-title">Courier Onboarding Status</span>
            <span
              class="status-tag"
              :class="approvalStatus === 'approved' ? 'active' : 'pending'"
            >
              {{ approvalStatus === 'approved' ? '✓ Approved Fleet Courier' : '⏳ Pending Approval' }}
            </span>
          </div>
          <div class="account-grid">
            <div class="grid-item">
              <label>Courier Name</label>
              <div class="value">{{ driverName }}</div>
            </div>
            <div class="grid-item">
              <label>Phone Contact</label>
              <div class="value">{{ driverPhone }}</div>
            </div>
            <div class="grid-item">
              <label>Vehicle Assignment</label>
              <div class="value">🛵 {{ vehicleType }}</div>
            </div>
            <div class="grid-item">
              <label>Assigned Hub</label>
              <div class="value">Bole Flagship Depot</div>
            </div>
          </div>
        </div>

        <!-- Teaser Courier Features -->
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">🗺️</div>
            <h3>Live GPS Dispatch</h3>
            <p>Real-time turn-by-turn routing directly from supermarket picking counter to customer drop pin.</p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">🔐</div>
            <h3>6-Digit Handover PIN</h3>
            <p>Customer provides OTP at the door to prevent misdelivered bags and guarantee proof of delivery.</p>
          </div>

          <div class="feature-card">
            <div class="feature-icon">📞</div>
            <h3>Yango-Style Direct Calling</h3>
            <p>One-tap in-app cellular calling to contact customer directly if gate access is restricted.</p>
          </div>
        </div>

        <!-- Stakeholder Switcher / Actions -->
        <div class="action-footer">
          <router-link to="/login" class="btn btn-secondary">
            ← Switch Stakeholder Account
          </router-link>
          <button @click="handleLogout" class="btn btn-primary">
            Sign Out Courier Portal
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.coming-soon-wrapper {
  min-height: 100vh;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  font-family: var(--font-sans);
}

.driver-header {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0.85rem 1.5rem;
}

.header-container {
  max-width: 1100px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.brand-logo {
  font-size: 1.6rem;
}

.brand-title {
  display: block;
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

.user-pill {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f1f5f9;
  padding: 0.4rem 0.85rem;
  border-radius: 9999px;
  border: 1px solid #e2e8f0;
}

.avatar-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #0284c7;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
}

.user-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.user-name {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.user-phone {
  font-size: 0.72rem;
  color: #64748b;
}

.logout-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 0.25rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.logout-btn:hover {
  background: #f87171;
  color: #ffffff;
  border-color: #ef4444;
}

.content-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2.5rem 1.5rem;
}

.hero-card {
  max-width: 860px;
  width: 100%;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 3rem 2.5rem;
  text-align: center;
  box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
}

.launch-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #e0f2fe;
  color: #0369a1;
  border: 1px solid #bae6fd;
  padding: 0.35rem 0.85rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 1.5rem;
}

.pulse-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #0284c7;
  box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7);
  animation: pulse 1.8s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 8px rgba(2, 132, 199, 0);
  }
  100% {
    transform: scale(0.95);
    box-shadow: 0 0 0 0 rgba(2, 132, 199, 0);
  }
}

.hero-title {
  font-size: 2.25rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
  margin-bottom: 1rem;
  letter-spacing: -0.02em;
}

.highlight-text {
  color: #0284c7;
}

.hero-desc {
  font-size: 1.05rem;
  color: #475569;
  max-width: 650px;
  margin: 0 auto 2.25rem auto;
  line-height: 1.6;
}

.account-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 2.5rem;
  text-align: left;
}

.account-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.account-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.status-tag {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
}

.status-tag.active {
  background: #dcfce7;
  color: #15803d;
}

.status-tag.pending {
  background: #fef3c7;
  color: #b45309;
}

.account-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}

.grid-item label {
  display: block;
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 0.2rem;
}

.grid-item .value {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
  margin-bottom: 2.5rem;
}

.feature-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: left;
  transition: all 0.2s ease;
}

.feature-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
  border-color: #cbd5e1;
}

.feature-icon {
  font-size: 1.8rem;
  margin-bottom: 0.75rem;
}

.feature-card h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.35rem;
}

.feature-card p {
  font-size: 0.825rem;
  color: #64748b;
  line-height: 1.5;
}

.action-footer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 1.4rem;
  font-size: 0.9rem;
  font-weight: 700;
  border-radius: 8px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.15s ease;
}

.btn-primary {
  background: #0f172a;
  color: #ffffff;
  border: 1px solid #0f172a;
}

.btn-primary:hover {
  background: #1e293b;
}

.btn-secondary {
  background: #f1f5f9;
  color: #334155;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

@media (max-width: 640px) {
  .hero-card {
    padding: 2rem 1.25rem;
  }
  .hero-title {
    font-size: 1.65rem;
  }
  .action-footer {
    flex-direction: column;
    width: 100%;
  }
  .btn {
    width: 100%;
  }
}
</style>
