import { defineStore } from 'pinia';

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: JSON.parse(localStorage.getItem('grocery_cart') || '[]'),
    deliveryType: 'express_immediate', // 'express_immediate' or 'scheduled_slot'
    paymentMethod: 'cash_on_delivery',
    specialNotes: '',
    deliveryFee: 50.00,
  }),

  getters: {
    totalCount: (state) => state.items.length,
    subtotal: (state) => {
      return state.items.reduce((sum, item) => {
        const price = item.product.discount_price ?? item.product.price;
        return sum + (price * item.quantity);
      }, 0);
    },
    estimatedTotal: (state) => {
      const sub = state.subtotal;
      return sub > 0 ? (sub + state.deliveryFee) : 0;
    },
    getItemQuantity: (state) => (productId) => {
      const found = state.items.find((i) => i.product.id === productId);
      return found ? found.quantity : 0;
    },
  },

  actions: {
    addItem(product, quantity = null) {
      const step = product.pricing_type === 'weighted' 
        ? (product.step_weight_grams ? product.step_weight_grams / 1000 : 0.5) 
        : 1;

      const qtyToAdd = quantity !== null ? quantity : step;
      const existing = this.items.find((i) => i.product.id === product.id);

      if (existing) {
        existing.quantity = Math.round((existing.quantity + qtyToAdd) * 100) / 100;
      } else {
        this.items.push({
          product,
          quantity: qtyToAdd,
          substitution_preference: 'best_match',
        });
      }

      this.save();
    },

    removeItem(productId) {
      this.items = this.items.filter((i) => i.product.id !== productId);
      this.save();
    },

    updateQuantity(productId, delta) {
      const item = this.items.find((i) => i.product.id === productId);
      if (!item) return;

      const step = item.product.pricing_type === 'weighted'
        ? (item.product.step_weight_grams ? item.product.step_weight_grams / 1000 : 0.5)
        : 1;

      const newQty = Math.round((item.quantity + (delta * step)) * 100) / 100;

      if (newQty <= 0) {
        this.removeItem(productId);
      } else {
        item.quantity = newQty;
        this.save();
      }
    },

    setSubstitution(productId, preference) {
      const item = this.items.find((i) => i.product.id === productId);
      if (item) {
        item.substitution_preference = preference;
        this.save();
      }
    },

    clearCart() {
      this.items = [];
      this.save();
    },

    save() {
      localStorage.setItem('grocery_cart', JSON.stringify(this.items));
    },
  },
});
