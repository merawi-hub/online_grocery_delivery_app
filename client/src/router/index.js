import { createRouter, createWebHistory } from 'vue-router';

// Store Manager Views (/admin123 platform)
import ManagerLoginView from '../views/manager/ManagerLoginView.vue';
import ManagerSignupView from '../views/manager/ManagerSignupView.vue';
import ManagerLayout from '../views/manager/ManagerLayout.vue';
import ManagerDashboardView from '../views/manager/ManagerDashboardView.vue';
import ManagerInventoryView from '../views/manager/ManagerInventoryView.vue';
import ManagerOrdersView from '../views/manager/ManagerOrdersView.vue';
import ManagerBranchView from '../views/manager/ManagerBranchView.vue';

// Other portals (kept available for later)
import HomeView from '../views/HomeView.vue';
import CartView from '../views/CartView.vue';
import TrackingView from '../views/TrackingView.vue';

const routes = [
  // Default entry: immediately land on Store Manager Dashboard
  {
    path: '/',
    redirect: '/admin123/dashboard',
  },

  // Manager Authentication
  {
    path: '/admin123/login',
    name: 'manager-login',
    component: ManagerLoginView,
  },
  {
    path: '/admin123/signup',
    name: 'manager-signup',
    component: ManagerSignupView,
  },

  // Manager Platform Workspace (Dedicated Layout with Manager Sidebar)
  {
    path: '/admin123',
    component: ManagerLayout,
    children: [
      {
        path: '',
        redirect: '/admin123/dashboard',
      },
      {
        path: 'dashboard',
        name: 'manager-dashboard',
        component: ManagerDashboardView,
      },
      {
        path: 'inventory',
        name: 'manager-inventory',
        component: ManagerInventoryView,
      },
      {
        path: 'orders',
        name: 'manager-orders',
        component: ManagerOrdersView,
      },
      {
        path: 'branch',
        name: 'manager-branch',
        component: ManagerBranchView,
      },
    ],
  },

  // Customer routes (standby)
  {
    path: '/customer',
    name: 'customer-store',
    component: HomeView,
  },
  {
    path: '/customer/cart',
    name: 'customer-cart',
    component: CartView,
  },
  {
    path: '/customer/tracking/:id?',
    name: 'customer-tracking',
    component: TrackingView,
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
