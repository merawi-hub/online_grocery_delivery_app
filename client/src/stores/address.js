import { defineStore } from 'pinia';
import api from '../services/api';

export const useAddressStore = defineStore('address', {
  state: () => ({
    addresses: [],
    selectedAddressId: null,
    loading: false,
  }),

  getters: {
    activeAddress: (state) => {
      if (!state.addresses.length) return null;
      if (state.selectedAddressId) {
        return state.addresses.find((a) => a.id === state.selectedAddressId) || state.addresses[0];
      }
      return state.addresses.find((a) => a.is_default) || state.addresses[0];
    },
  },

  actions: {
    async fetchAddresses() {
      this.loading = true;
      try {
        const res = await api.get('/addresses');
        this.addresses = res.data;
        if (!this.selectedAddressId && this.addresses.length > 0) {
          const def = this.addresses.find((a) => a.is_default);
          this.selectedAddressId = def ? def.id : this.addresses[0].id;
        }
      } catch (err) {
        console.error('Failed to load addresses', err);
      } finally {
        this.loading = false;
      }
    },

    async saveAddress(addressData) {
      this.loading = true;
      try {
        const res = await api.post('/addresses', addressData);
        await this.fetchAddresses();
        this.selectedAddressId = res.data.address.id;
        return { success: true, address: res.data.address };
      } catch (err) {
        const message = err.response?.data?.message || 'Failed to save address';
        return { success: false, error: message };
      } finally {
        this.loading = false;
      }
    },

    selectAddress(id) {
      this.selectedAddressId = id;
    },
  },
});
