<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../services/api';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

// Get return URL from query params
const returnUrl = computed(() => route.query.returnUrl || null);

// Mode: 'login' or 'register'
const mode = ref('login');

// Login inputs
const loginInput = ref('');
const passwordInput = ref('');

// Registration inputs
const regName = ref('');
const regPhone = ref('');
const regEmail = ref('');
const regPassword = ref('');
const regConfirmPassword = ref('');

// State
const errorMessage = ref('');
const isLoading = ref(false);

function switchMode(newMode) {
  mode.value = newMode;
  errorMessage.value = '';
  clearInputs();
}

function clearInputs() {
  loginInput.value = '';
  passwordInput.value = '';
  regName.value = '';
  regPhone.value = '';
  regEmail.value = '';
  regPassword.value = '';
  regConfirmPassword.value = '';
}

async function handleLogin() {
  if (!loginInput.value || !passwordInput.value) {
    errorMessage.value = 'Please fill in all required fields';
    return;
  }

  errorMessage.value = '';
  isLoading.value = true;

  const result = await authStore.login(loginInput.value, passwordInput.value);
  isLoading.value = false;

  if (result.success) {
    // Role-based redirect
    if (returnUrl.value && authStore.userRole === 'customer') {
      // If there's a return URL and user is customer, go back
      router.push(returnUrl.value);
    } else if (authStore.userRole === 'admin') {
      router.push('/admin/dashboard');
    } else if (authStore.userRole === 'store_manager') {
      router.push('/admin123/dashboard');
    } else if (authStore.userRole === 'driver') {
      router.push('/driver');
    } else {
      // Default for customers
      router.push('/account');
    }
  } else {
    errorMessage.value = result.error || 'Invalid credentials';
  }
}

async function handleRegister() {
  errorMessage.value = '';

  if (!regName.value || !regPhone.value || !regPassword.value || !regConfirmPassword.value) {
    errorMessage.value = 'Please fill in all required fields';
    return;
  }

  if (regPassword.value !== regConfirmPassword.value) {
    errorMessage.value = 'Passwords do not match';
    return;
  }

  if (regPassword.value.length < 6) {
    errorMessage.value = 'Password must be at least 6 characters';
    return;
  }

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

    // After registration, redirect to return URL or account
    if (returnUrl.value) {
      router.push(returnUrl.value);
    } else {
      router.push('/account');
    }
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Registration failed';
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-container">
      <!-- Header -->
      <div class="auth-header">
        <router-link to="/" class="back-link">← Back to Home</router-link>
      </div>

      <!-- Content -->
      <div class="auth-content">
        <!-- Left Side - Image/Branding -->
        <div class="auth-visual">
          <div class="visual-content">
            <h1 class="visual-title">Fresh<span>Mart</span></h1>
            <p class="visual-subtitle">Online Grocery Shopping</p>
          </div>
        </div>

        <!-- Right Side - Form -->
        <div class="auth-form-container">
          <!-- Mode Toggle -->
          <div class="mode-toggle">
            <button
              type="button"
              :class="{ active: mode === 'login' }"
              @click="switchMode('login')"
            >
              Sign in
            </button>
            <button
              type="button"
              :class="{ active: mode === 'register' }"
              @click="switchMode('register')"
            >
              Create account
            </button>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="error-msg">
            {{ errorMessage }}
          </div>

          <!-- Login Form -->
          <form v-if="mode === 'login'" @submit.prevent="handleLogin" class="form">
            <div class="form-field">
              <input
                type="text"
                v-model="loginInput"
                placeholder="Phone number or username"
                required
              />
            </div>

            <div class="form-field">
              <input
                type="password"
                v-model="passwordInput"
                placeholder="Password"
                required
              />
            </div>

            <button type="submit" class="btn-primary" :disabled="isLoading">
              {{ isLoading ? 'Signing in...' : 'Sign in' }}
            </button>
          </form>

          <!-- Register Form -->
          <form v-else @submit.prevent="handleRegister" class="form">
            <div class="form-field">
              <input
                type="text"
                v-model="regName"
                placeholder="Full name"
                required
              />
            </div>

            <div class="form-field">
              <input
                type="tel"
                v-model="regPhone"
                placeholder="Phone number"
                required
              />
            </div>

            <div class="form-field">
              <input
                type="email"
                v-model="regEmail"
                placeholder="Email (optional)"
              />
            </div>

            <div class="form-field">
              <input
                type="password"
                v-model="regPassword"
                placeholder="Password (min 6 characters)"
                required
              />
            </div>

            <div class="form-field">
              <input
                type="password"
                v-model="regConfirmPassword"
                placeholder="Confirm password"
                required
              />
            </div>

            <button type="submit" class="btn-primary" :disabled="isLoading">
              {{ isLoading ? 'Creating account...' : 'Create account' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: #fafaf9;
  font-family: 'Amazon Ember', Arial, sans-serif;
}

.auth-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0;
}

/* Header */
.auth-header {
  padding: 1.5rem 2rem;
}

.back-link {
  color: #007185;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
}

.back-link:hover {
  color: #C7511F;
  text-decoration: underline;
}

/* Content */
.auth-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: calc(100vh - 100px);
  gap: 0;
}

/* Visual Side */
.auth-visual {
  background: linear-gradient(135deg, #E3F4E8 0%, #D5EED9 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}

.visual-content {
  text-align: center;
}

.visual-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #0F1111;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.visual-title span {
  color: #067D62;
}

.visual-subtitle {
  font-size: 1.125rem;
  color: #565959;
  font-weight: 400;
}

/* Form Side */
.auth-form-container {
  background: #ffffff;
  padding: 3rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  max-width: 450px;
  margin: 0 auto;
}

/* Mode Toggle */
.mode-toggle {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.mode-toggle button {
  flex: 1;
  padding: 0.75rem;
  background: #ffffff;
  border: 1px solid #D5D9D9;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0F1111;
  cursor: pointer;
  transition: all 0.2s;
}

.mode-toggle button:hover {
  background: #F7F8F8;
  border-color: #A2A6AC;
}

.mode-toggle button.active {
  background: #FFF4E6;
  border-color: #F08804;
  color: #0F1111;
}

/* Error Message */
.error-msg {
  background: #FEF2F2;
  border: 1px solid #FCA5A5;
  border-radius: 8px;
  padding: 0.875rem 1rem;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  color: #991B1B;
}

/* Form */
.form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-field input {
  width: 100%;
  padding: 0.875rem 1rem;
  font-size: 0.875rem;
  background: #ffffff;
  border: 1px solid #D5D9D9;
  border-radius: 8px;
  outline: none;
  transition: all 0.2s;
  color: #0F1111;
}

.form-field input::placeholder {
  color: #6B7280;
}

.form-field input:focus {
  border-color: #F08804;
  box-shadow: 0 0 0 3px #FFF4E6;
}

/* Button */
.btn-primary {
  width: 100%;
  padding: 0.875rem 1rem;
  background: linear-gradient(to bottom, #F7DDBF, #F0C491);
  border: 1px solid #AE7B3E;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  color: #0F1111;
  cursor: pointer;
  margin-top: 0.5rem;
  transition: all 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(to bottom, #F5D4A6, #EDB976);
  border-color: #9B6B31;
}

.btn-primary:active:not(:disabled) {
  background: #EDB976;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1024px) {
  .auth-content {
    grid-template-columns: 1fr;
  }

  .auth-visual {
    padding: 2rem;
    min-height: 200px;
  }

  .visual-title {
    font-size: 2.5rem;
  }

  .auth-form-container {
    padding: 2rem;
  }
}

@media (max-width: 640px) {
  .auth-header {
    padding: 1rem;
  }

  .auth-visual {
    padding: 1.5rem;
  }

  .visual-title {
    font-size: 2rem;
  }

  .visual-subtitle {
    font-size: 1rem;
  }

  .auth-form-container {
    padding: 1.5rem;
  }

  .mode-toggle {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
