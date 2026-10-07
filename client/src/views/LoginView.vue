<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const isRegister = ref(false);
const loginInput = ref('+251911000005'); // Default customer
const passwordInput = ref('password123');

// Register fields
const regName = ref('');
const regPhone = ref('');
const regEmail = ref('');
const regPassword = ref('');

const errorMessage = ref('');
const successMessage = ref('');
const isLoading = ref(false);

async function handleLogin() {
  errorMessage.value = '';
  isLoading.value = true;
  const result = await authStore.login(loginInput.value, passwordInput.value);
  isLoading.value = false;

  if (result.success) {
    navigateAfterAuth();
  } else {
    errorMessage.value = result.error;
  }
}

async function handleRegister() {
  errorMessage.value = '';
  successMessage.value = '';
  isLoading.value = true;
  try {
    const res = await api.post('/auth/register', {
      name: regName.value,
      phone_number: regPhone.value,
      email: regEmail.value || null,
      password: regPassword.value,
    });

    authStore.token = res.data.token;
    authStore.user = res.data.user;
    localStorage.setItem('grocery_token', res.data.token);
    localStorage.setItem('grocery_user', JSON.stringify(res.data.user));

    navigateAfterAuth();
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Registration failed. Please check inputs.';
  } finally {
    isLoading.value = false;
  }
}

function navigateAfterAuth() {
  const role = authStore.userRole;
  const redirect = route.query.redirect;
  if (redirect) {
    router.push(redirect);
    return;
  }

  if (role === 'picker') {
    router.push('/picker');
  } else if (role === 'driver') {
    router.push('/driver');
  } else {
    router.push('/');
  }
}

function quickLogin(phone, password) {
  loginInput.value = phone;
  passwordInput.value = password;
  handleLogin();
}
</script>

<template>
  <div class="auth-wrapper">
    <!-- LEFT PANEL: Hero & Platform Highlights -->
    <div class="auth-hero-panel">
      <div class="hero-brand">
        <span class="hero-brand-icon">🛒</span>
        <span class="hero-brand-name">FreshSupermarket</span>
      </div>

      <div class="hero-text-group">
        <span class="hero-badge">⚡ Enterprise Grocery Delivery OS</span>
        <h1 class="hero-title">
          In-Store Picking & <br />
          <span class="highlight">Turn-by-Turn GPS Dispatch</span>
        </h1>
        <p class="hero-desc">
          Unified platform connecting customers with exact building drop pins, supermarket pickers with scale weight validation, and delivery couriers with live Google Maps routing.
        </p>

        <!-- Feature Points -->
        <div class="feature-list">
          <div class="feature-item">
            <span class="feature-icon">🏠</span>
            <div>
              <strong>Precision Address Engine</strong>
              <p>House #, floor, door code, and landmark notes for instant findability.</p>
            </div>
          </div>

          <div class="feature-item">
            <span class="feature-icon">⚖️</span>
            <div>
              <strong>Weighted Produce Fulfillment</strong>
              <p>Automatic weight-adjusted pricing on fresh meat, cheese, and fruits.</p>
            </div>
          </div>

          <div class="feature-item">
            <span class="feature-icon">🛵</span>
            <div>
              <strong>Yango-Style Direct Calling</strong>
              <p>One-tap driver-to-customer phone calling and 6-digit delivery PIN.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="hero-footer">
        <span>Active Supermarket Branch: Addis Ababa Bole Medhanialem Flagship</span>
      </div>
    </div>

    <!-- RIGHT PANEL: Authentication Form & Stakeholder Switcher -->
    <div class="auth-form-panel">
      <div class="auth-card">
        <!-- Auth Type Tabs -->
        <div class="auth-tabs">
          <button
            class="tab-btn"
            :class="{ active: !isRegister }"
            @click="isRegister = false; errorMessage = '';"
          >
            Sign In
          </button>
          <button
            class="tab-btn"
            :class="{ active: isRegister }"
            @click="isRegister = true; errorMessage = '';"
          >
            Create Customer Account
          </button>
        </div>

        <div v-if="errorMessage" class="error-banner">{{ errorMessage }}</div>
        <div v-if="successMessage" class="success-banner">{{ successMessage }}</div>

        <!-- 1. SIGN IN FORM -->
        <form v-if="!isRegister" @submit.prevent="handleLogin" class="form-body">
          <div class="form-group">
            <label>Mobile Phone or Email</label>
            <div class="input-wrap">
              <span class="input-icon">📱</span>
              <input
                type="text"
                v-model="loginInput"
                required
                placeholder="+251911000005 or email@test.com"
              />
            </div>
          </div>

          <div class="form-group">
            <div class="label-row">
              <label>Password</label>
              <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            <div class="input-wrap">
              <span class="input-icon">🔒</span>
              <input
                type="password"
                v-model="passwordInput"
                required
                placeholder="••••••••"
              />
            </div>
          </div>

          <button type="submit" class="btn btn-primary submit-btn" :disabled="isLoading">
            {{ isLoading ? 'Verifying Credentials...' : 'Sign In to Portal →' }}
          </button>
        </form>

        <!-- 2. REGISTRATION FORM -->
        <form v-else @submit.prevent="handleRegister" class="form-body">
          <div class="form-group">
            <label>Full Name</label>
            <div class="input-wrap">
              <span class="input-icon">👤</span>
              <input type="text" v-model="regName" required placeholder="Abebe Bikila" />
            </div>
          </div>

          <div class="form-group">
            <label>Mobile Phone (For Courier Call)</label>
            <div class="input-wrap">
              <span class="input-icon">📞</span>
              <input type="text" v-model="regPhone" required placeholder="+251911223344" />
            </div>
          </div>

          <div class="form-group">
            <label>Email (Optional)</label>
            <div class="input-wrap">
              <span class="input-icon">✉️</span>
              <input type="email" v-model="regEmail" placeholder="name@example.com" />
            </div>
          </div>

          <div class="form-group">
            <label>Create Password</label>
            <div class="input-wrap">
              <span class="input-icon">🔒</span>
              <input type="password" v-model="regPassword" required placeholder="At least 6 characters" />
            </div>
          </div>

          <button type="submit" class="btn btn-primary submit-btn" :disabled="isLoading">
            {{ isLoading ? 'Creating Account...' : 'Register & Start Shopping →' }}
          </button>
        </form>

        <!-- 3. STAKEHOLDER DEMO ONE-CLICK BUTTONS -->
        <div class="demo-switch-box">
          <span class="demo-title">⚡ Instant Test Login (3 Key Stakeholders):</span>
          <div class="demo-stakeholders">
            <button
              class="stakeholder-card customer"
              @click="quickLogin('+251911000005', 'password123')"
            >
              <span class="stake-icon">🛒</span>
              <div class="stake-meta">
                <strong>Customer (Shopper)</strong>
                <small>Marta • +251911000005</small>
              </div>
              <span class="stake-arrow">→</span>
            </button>

            <button
              class="stakeholder-card picker"
              @click="quickLogin('+251911000003', 'password123')"
            >
              <span class="stake-icon">📦</span>
              <div class="stake-meta">
                <strong>Supermarket Picker</strong>
                <small>Dawit • +251911000003</small>
              </div>
              <span class="stake-arrow">→</span>
            </button>

            <button
              class="stakeholder-card driver"
              @click="quickLogin('+251911000004', 'password123')"
            >
              <span class="stake-icon">🛵</span>
              <div class="stake-meta">
                <strong>Delivery Courier</strong>
                <small>Yonas • +251911000004</small>
              </div>
              <span class="stake-arrow">→</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-wrapper {
  display: flex;
  min-height: 100vh;
  width: 100%;
}

/* Left Hero Panel */
.auth-hero-panel {
  flex: 1.1;
  background: linear-gradient(145deg, #0f172a 0%, #064e3b 100%);
  color: #ffffff;
  padding: 3.5rem 4rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.hero-brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.hero-brand-icon {
  font-size: 2rem;
  background: rgba(16, 185, 129, 0.2);
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(16, 185, 129, 0.4);
}

.hero-brand-name {
  font-size: 1.45rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.hero-text-group {
  max-width: 540px;
  margin: 3rem 0;
}

.hero-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 800;
  color: #34d399;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.85rem;
}

.hero-title {
  font-size: 2.6rem;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.03em;
  margin-bottom: 1rem;
}

.highlight {
  color: #34d399;
}

.hero-desc {
  font-size: 1rem;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 2rem;
}

.feature-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.feature-icon {
  font-size: 1.4rem;
  background: rgba(255, 255, 255, 0.08);
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.feature-item strong {
  display: block;
  font-size: 0.95rem;
  color: #ffffff;
}

.feature-item p {
  font-size: 0.825rem;
  color: #94a3b8;
  margin-top: 0.15rem;
}

.hero-footer {
  font-size: 0.78rem;
  color: #64748b;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 1.5rem;
}

/* Right Form Panel */
.auth-form-panel {
  flex: 0.9;
  background: var(--bg-main);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
}

.auth-card {
  width: 100%;
  max-width: 460px;
  background: #ffffff;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: 2.25rem 2.25rem;
  box-shadow: var(--shadow-md);
}

.auth-tabs {
  display: flex;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 1.5rem;
}

.tab-btn {
  flex: 1;
  padding: 0.75rem 0.5rem;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--text-muted);
  border-bottom: 2px solid transparent;
  transition: var(--transition);
}

.tab-btn.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
}

.form-body {
  display: flex;
  flex-direction: column;
  gap: 1.1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-group label {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--dark);
}

.forgot-link {
  font-size: 0.75rem;
  color: var(--primary);
  font-weight: 600;
}

.input-wrap {
  display: flex;
  align-items: center;
  border: 1.5px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: 0.65rem 0.85rem;
  background: #ffffff;
  transition: var(--transition);
}

.input-wrap:focus-within {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--primary-glow);
}

.input-icon {
  font-size: 1.1rem;
  margin-right: 0.65rem;
  color: var(--text-light);
}

.input-wrap input {
  flex: 1;
  border: none;
  font-size: 0.9rem;
  color: var(--dark);
}

.submit-btn {
  width: 100%;
  padding: 0.85rem;
  font-size: 0.95rem;
  margin-top: 0.5rem;
}

.error-banner {
  background: #fee2e2;
  color: #b91c1c;
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.825rem;
  margin-bottom: 1rem;
}

.success-banner {
  background: #d1fae5;
  color: #065f46;
  padding: 0.75rem;
  border-radius: var(--radius-sm);
  font-size: 0.825rem;
  margin-bottom: 1rem;
}

/* Stakeholder Demo Box */
.demo-switch-box {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px dashed var(--border-color);
}

.demo-title {
  display: block;
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
}

.demo-stakeholders {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stakeholder-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  text-align: left;
  transition: var(--transition);
}

.stakeholder-card:hover {
  border-color: var(--primary);
  background: var(--primary-light);
  transform: translateX(2px);
}

.stake-icon {
  font-size: 1.35rem;
}

.stake-meta {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.stake-meta strong {
  font-size: 0.825rem;
  color: var(--dark);
}

.stake-meta small {
  font-size: 0.7rem;
  color: var(--text-muted);
}

.stake-arrow {
  font-size: 0.9rem;
  color: var(--text-light);
  font-weight: 700;
}

@media (max-width: 960px) {
  .auth-hero-panel {
    display: none;
  }
}
</style>
