<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const loginInput = ref('admin');
const passwordInput = ref('admin123');
const errorMessage = ref('');
const successMessage = ref('');
const isLoading = ref(false);
const isFullscreen = ref(false);

async function handleLogin() {
  errorMessage.value = '';
  successMessage.value = '';
  isLoading.value = true;

  const result = await authStore.login(loginInput.value, passwordInput.value);
  isLoading.value = false;

  if (result.success) {
    if (authStore.userRole === 'admin') {
      router.push('/general-manager');
    } else if (authStore.userRole === 'store_manager') {
      router.push('/admin123/dashboard');
    } else {
      router.push('/general-manager');
    }
  } else {
    errorMessage.value = result.error || 'Invalid credentials. Please verify your login details.';
  }
}

function quickFillDemo() {
  loginInput.value = 'admin';
  passwordInput.value = 'admin123';
  handleLogin();
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
    isFullscreen.value = true;
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen().catch(() => {});
      isFullscreen.value = false;
    }
  }
}

onMounted(() => {
  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement;
  });
});
</script>

<template>
  <div class="exact-design-viewport">
    <main class="design-stage-container">
      <div class="stage-frame">
        <!-- Exact Uploaded Reference Image As Base -->
        <img
          src="/images/exact-admin-design.png"
          alt="Admin Login"
          class="exact-reference-image"
        />

        <!-- Feedback Messages Overlay -->
        <div v-if="errorMessage" class="floating-alert error-alert">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="floating-alert success-alert">
          {{ successMessage }}
        </div>

        <!-- Interactive Form Layer Positioned Over Exact Inputs -->
        <form @submit.prevent="handleLogin" class="interactive-overlay-form">
          <!-- Username / Login Id Input -->
          <div class="field-hitbox username-hitbox">
            <input
              type="text"
              v-model="loginInput"
              placeholder="Login Id*"
              required
              autocomplete="username"
              class="active-pill-input"
            />
          </div>

          <!-- Password Input -->
          <div class="field-hitbox password-hitbox">
            <input
              type="password"
              v-model="passwordInput"
              placeholder="Password*"
              required
              autocomplete="current-password"
              class="active-pill-input"
            />
          </div>

          <!-- Blue Login Button -->
          <button
            type="submit"
            class="button-hitbox login-button-hitbox"
            :disabled="isLoading"
            title="Click to Login"
          >
            <span v-if="isLoading" class="btn-spinner"></span>
            <span class="btn-text">{{ isLoading ? 'LOGGING IN...' : 'LOGIN' }}</span>
          </button>
        </form>

        <!-- Floating Controls Hitboxes -->
        <button
          type="button"
          class="action-hitbox fullscreen-hitbox"
          @click="toggleFullscreen"
          title="Toggle Fullscreen"
        ></button>

        <button
          type="button"
          class="action-hitbox reset-hitbox"
          @click="quickFillDemo"
          title="Reset and 1-Click Fill Demo Credentials (admin / admin123)"
        ></button>
      </div>
    </main>

    <!-- Bottom Helper -->
    <footer class="bottom-helper-bar">
      <div class="credentials-badge" @click="quickFillDemo">
        <span>⚡ <strong>Quick Test Credentials:</strong> admin / admin123</span>
        <span class="role-tag">General Operations Manager</span>
      </div>
      <router-link to="/login" class="back-link">
        ← Back to All Portals
      </router-link>
    </footer>
  </div>
</template>

<style scoped>
.exact-design-viewport {
  position: relative;
  width: 100vw;
  height: 100vh;
  background-color: #0c0f14;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  user-select: none;
}

.design-stage-container {
  flex: 1;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
}

.stage-frame {
  position: relative;
  width: min(96vw, calc(92vh * (655 / 432)));
  height: min(calc(96vw * (432 / 655)), 92vh);
  max-width: 1100px;
  max-height: 725px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85);
  border-radius: 8px;
  overflow: hidden;
  background-color: #000000;
}

.exact-reference-image {
  width: 100%;
  height: 100%;
  object-fit: fill;
  display: block;
  pointer-events: none;
}

/* Floating Alerts */
.floating-alert {
  position: absolute;
  top: 3%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 40;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.82rem;
  font-weight: 600;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translate(-50%, -10px); }
  to { opacity: 1; transform: translate(-50%, 0); }
}

.error-alert {
  background: #dc2626;
  color: #ffffff;
  border: 1px solid #ef4444;
}

.success-alert {
  background: #059669;
  color: #ffffff;
  border: 1px solid #10b981;
}

/* Interactive Hitbox Overlays */
.interactive-overlay-form {
  position: absolute;
  inset: 0;
}

.username-hitbox {
  position: absolute;
  left: 29.16%;
  top: 32.64%;
  width: 43.97%;
  height: 11.11%;
  display: flex;
  align-items: center;
  padding-left: 9%;
  padding-right: 4%;
  box-sizing: border-box;
}

.password-hitbox {
  position: absolute;
  left: 29.16%;
  top: 46.53%;
  width: 43.97%;
  height: 11.11%;
  display: flex;
  align-items: center;
  padding-left: 9%;
  padding-right: 4%;
  box-sizing: border-box;
}

.active-pill-input {
  width: 100%;
  height: 80%;
  background: transparent;
  border: none;
  outline: none;
  font-size: clamp(0.85rem, 1.8vw, 1.15rem);
  font-weight: 600;
  color: #111827;
  padding: 0 0.5rem;
  box-sizing: border-box;
}

.active-pill-input::placeholder {
  color: transparent;
}

.active-pill-input:focus {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 9999px;
  box-shadow: 0 0 0 2px #3b82f6;
  color: #111827;
}

.username-hitbox:focus-within,
.password-hitbox:focus-within {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 9999px;
  box-shadow: 0 0 0 2.5px #3b82f6;
}

.login-button-hitbox {
  position: absolute;
  left: 29.16%;
  top: 63.66%;
  width: 43.97%;
  height: 9.8%;
  background: rgba(59, 130, 246, 0.05);
  border: none;
  border-radius: 9999px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.login-button-hitbox:hover:not(:disabled) {
  background: rgba(59, 130, 246, 0.4);
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.6);
}

.login-button-hitbox:active:not(:disabled) {
  transform: scale(0.99);
}

.btn-text {
  color: transparent;
  font-size: clamp(0.8rem, 1.6vw, 1.05rem);
  font-weight: 700;
  letter-spacing: 0.08em;
}

.login-button-hitbox:disabled .btn-text {
  color: #ffffff;
}

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.action-hitbox {
  position: absolute;
  background: rgba(255, 255, 255, 0.05);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.15s ease;
}

.action-hitbox:hover {
  background: rgba(255, 255, 255, 0.25);
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.4);
}

.fullscreen-hitbox {
  left: 91.3%;
  top: 62.5%;
  width: 7.6%;
  height: 11.5%;
}

.reset-hitbox {
  left: 91.3%;
  top: 79.8%;
  width: 7.6%;
  height: 11.5%;
}

.bottom-helper-bar {
  width: 100%;
  padding: 0.6rem 1rem;
  background: rgba(12, 15, 20, 0.8);
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.credentials-badge {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.06);
  padding: 0.35rem 1rem;
  border-radius: 9999px;
  font-size: 0.78rem;
  color: #cbd5e1;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
}

.role-tag {
  color: #94a3b8;
  font-size: 0.72rem;
}

.back-link {
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.78rem;
}

.back-link:hover {
  color: #ffffff;
  text-decoration: underline;
}
</style>
