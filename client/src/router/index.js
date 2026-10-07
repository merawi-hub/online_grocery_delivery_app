import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import CartView from '../views/CartView.vue';
import TrackingView from '../views/TrackingView.vue';
import LoginView from '../views/LoginView.vue';
import PickerView from '../views/picker/PickerView.vue';
import DriverView from '../views/driver/DriverView.vue';

const routes = [
  // 1. Customer Stakeholder Pages
  {
    path: '/',
    name: 'customer-store',
    component: HomeView,
  },
  {
    path: '/cart',
    name: 'customer-cart',
    component: CartView,
  },
  {
    path: '/tracking/:id?',
    name: 'customer-tracking',
    component: TrackingView,
  },

  // 2. Picker Stakeholder Page
  {
    path: '/picker',
    name: 'picker-station',
    component: PickerView,
  },

  // 3. Driver Stakeholder Page
  {
    path: '/driver',
    name: 'driver-console',
    component: DriverView,
  },

  // Authentication Page
  {
    path: '/login',
    name: 'auth',
    component: LoginView,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

export default router;
