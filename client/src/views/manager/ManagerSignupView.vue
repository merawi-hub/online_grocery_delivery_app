<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  name: '',
  phone_number: '',
  email: '',
  branch_name: 'Bole Medhanialem Flagship',
  password: '',
  confirm_password: '',
});

const errorMessage = ref('');
const isLoading = ref(false);

async function handleManagerSignup() {
  errorMessage.value = '';

  if (form.value.password !== form.value.confirm_password) {
    errorMessage.value = 'Passwords do not match.';
    return;
  }

  isLoading.value = true;
  try {
    const res = await api.post('/manager/register', {
      name: form.value.name,
      phone_number: form.value.phone_number,
      email: form.value.email,
      branch_name: form.value.branch_name,
      password: form.value.password,
    });

    authStore.token = res.data.token;
    authStore.user = res.data.user;
    localStorage.setItem('grocery_token', res.data.token);
    localStorage.setItem('grocery_user', JSON.stringify(res.data.user));

    router.push('/admin123/dashboard');
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to register manager. Please verify your details.';
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-box">
      <!-- Header -->
      <div class="auth-header">
        <h2 class="auth-title">Register Store Manager</h2>
        <p class="auth-sub">FreshMart Supermarket Branch Account Onboarding</p>
      </div>

      <div v-if="errorMessage" class="error-banner">
        {{ errorMessage }}
      </div>

      <!-- Registration Form -->
      <form class="auth-form" @submit.prevent="handleManagerSignup">
        <div class="form-row">
          <div class="form-group flex-1">
            <label>Full Name *</label>
            <input type="text" v-model="form.name" required placeholder="e.g. Sara Haile" class="form-input" />
          </div>

          <div class="form-group flex-1">
            <label>Phone Number *</label>
            <input type="text" v-model="form.phone_number" required placeholder="+251911000002" class="form-input" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group flex-1">
            <label>Work Email *</label>
            <input type="email" v-model="form.email" required placeholder="manager@freshmart.et" class="form-input" />
          </div>

          <div class="form-group flex-1">
            <label>Supermarket Branch *</label>
            <input type="text" v-model="form.branch_name" required placeholder="Bole Flagship" class="form-input" />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group flex-1">
            <label>Password *</label>
            <input type="password" v-model="form.password" required placeholder="Minimum 6 characters" class="form-input" />
          </div>

          <div class="form-group flex-1">
            <label>Confirm Password *</label>
            <input type="password" v-model="form.confirm_password" required placeholder="Repeat password" class="form-input" />
          </div>
        </div>

        <button type="submit" class="btn btn-primary submit-btn" :disabled="isLoading">
          {{ isLoading ? 'Creating Account...' : 'Register Manager Profile' }}
        </button>
      </form>

      <div class="auth-footer">
        <span>Already have an account?</span>
        <router-link to="/admin123/login" class="footer-link">
          Log In
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
  max-width: 520px;
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

.form-row {
  display: flex;
  gap: 0.85rem;
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
  font-weight: 600;
  color: #374151;
}

.submit-btn {
  width: 100%;
  padding: 0.6rem;
  font-size: 0.875rem;
  margin-top: 0.5rem;
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
