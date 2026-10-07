<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const loginInput = ref('+251911000002');
const passwordInput = ref('password123');
const errorMessage = ref('');
const isLoading = ref(false);

async function handleManagerLogin() {
  errorMessage.value = '';
  isLoading.value = true;
  const res = await authStore.login(loginInput.value, passwordInput.value);
  isLoading.value = false;

  if (res.success) {
    router.push('/admin123/dashboard');
  } else {
    errorMessage.value = res.error || 'Invalid manager credentials.';
  }
}

function quickFill(phone) {
  loginInput.value = phone;
  passwordInput.value = 'password123';
  handleManagerLogin();
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-box">
      <!-- Brand Header -->
      <div class="auth-header">
        <h2 class="auth-title">Store Manager Login</h2>
        <p class="auth-sub">FreshMart Supermarket Management Console</p>
      </div>

      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <!-- Login Form -->
      <form class="auth-form" @submit.prevent="handleManagerLogin">
        <div class="form-group">
          <label>Phone Number or Email</label>
          <input
            type="text"
            v-model="loginInput"
            required
            placeholder="+251911000002"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input
            type="password"
            v-model="passwordInput"
            required
            placeholder="••••••••"
            class="form-input"
          />
        </div>

        <button type="submit" class="btn btn-primary submit-btn" :disabled="isLoading">
          {{ isLoading ? 'Signing In...' : 'Sign In to Dashboard' }}
        </button>
      </form>

      <!-- Pre-Configured Manager Account -->
      <div class="quick-account-section">
        <span class="quick-label">Pre-configured Demo Account</span>
        <button
          type="button"
          class="quick-account-card"
          @click="quickFill('+251911000002')"
        >
          <div class="account-meta">
            <strong>Sara Manager</strong>
            <small>+251911000002 • Bole Branch</small>
          </div>
          <span class="quick-action">One-Click Login →</span>
        </button>
      </div>

      <!-- Bottom Nav -->
      <div class="auth-footer">
        <span>Need a new manager profile?</span>
        <router-link to="/admin123/signup" class="footer-link">
          Register Manager
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  width: 100vw;
  background: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.auth-box {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.auth-header {
  margin-bottom: 1.5rem;
}

.auth-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.01em;
}

.auth-sub {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 0.2rem;
}

.error-banner {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
  padding: 0.65rem 0.85rem;
  border-radius: 6px;
  font-size: 0.8rem;
  margin-bottom: 1.25rem;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-group label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #374151;
}

.submit-btn {
  width: 100%;
  padding: 0.6rem;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.quick-account-section {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e5e7eb;
}

.quick-label {
  display: block;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  letter-spacing: 0.04em;
  margin-bottom: 0.5rem;
}

.quick-account-card {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.65rem 0.85rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
  text-align: left;
}

.quick-account-card:hover {
  background: #ffffff;
  border-color: #111827;
}

.account-meta strong {
  display: block;
  font-size: 0.825rem;
  color: #111827;
}

.account-meta small {
  font-size: 0.72rem;
  color: #6b7280;
}

.quick-action {
  font-size: 0.75rem;
  font-weight: 600;
  color: #111827;
}

.auth-footer {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.78rem;
  color: #6b7280;
}

.footer-link {
  color: #111827;
  font-weight: 600;
}

.footer-link:hover {
  text-decoration: underline;
}
</style>
