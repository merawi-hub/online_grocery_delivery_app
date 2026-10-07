import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('grocery_user') || 'null'),
    token: localStorage.getItem('grocery_token') || null,
    loading: false,
    selectedBranchId: 1, // Default to Bole Medhanialem Flagship Branch
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    userName: (state) => state.user?.name || 'Guest',
    userRole: (state) => state.user?.role || 'customer',
    userPhone: (state) => state.user?.phone_number || '',
  },

  actions: {
    async login(loginIdentifier, password) {
      this.loading = true;
      try {
        const response = await api.post('/auth/login', {
          login: loginIdentifier,
          password: password,
        });

        this.token = response.data.token;
        this.user = response.data.user;

        localStorage.setItem('grocery_token', this.token);
        localStorage.setItem('grocery_user', JSON.stringify(this.user));

        return { success: true };
      } catch (err) {
        const message = err.response?.data?.message || 'Login failed. Please check your credentials.';
        return { success: false, error: message };
      } finally {
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return;
      try {
        const response = await api.get('/auth/me');
        this.user = response.data.user;
        localStorage.setItem('grocery_user', JSON.stringify(this.user));
      } catch (e) {
        this.logout();
      }
    },

    async logout() {
      if (this.token) {
        try {
          await api.post('/auth/logout');
        } catch (_) {}
      }
      this.user = null;
      this.token = null;
      localStorage.removeItem('grocery_token');
      localStorage.removeItem('grocery_user');
    },

    setBranch(branchId) {
      this.selectedBranchId = branchId;
    },
  },
});
