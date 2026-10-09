import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Layouts
import PublicLayout from '../layouts/PublicLayout.vue';

// Authentication Views
import LoginView from '../views/LoginView.vue';

// Customer Views
import HomeView from '../views/HomeView.vue';
import CartView from '../views/CartView.vue';
import CustomerDashboard from '../views/customer/CustomerDashboard.vue';
import TrackingView from '../views/TrackingView.vue';

// Manager Views
import ManagerLoginView from '../views/manager/ManagerLoginView.vue';
import ManagerSignupView from '../views/manager/ManagerSignupView.vue';
import ManagerLayout from '../views/manager/ManagerLayout.vue';
import ManagerDashboardView from '../views/manager/ManagerDashboardView.vue';
import ManagerInventoryView from '../views/manager/ManagerInventoryView.vue';
import ManagerOrdersView from '../views/manager/ManagerOrdersView.vue';
import ManagerBranchView from '../views/manager/ManagerBranchView.vue';

// Driver Views
import DelivererComingSoonView from '../views/driver/DelivererComingSoonView.vue';

// Admin Views
import GeneralManagerView from '../views/admin/GeneralManagerView.vue';
import AdminLoginView from '../views/admin/AdminLoginView.vue';

const routes = [
  // PUBLIC ROUTES (No Auth Required) - Use PublicLayout
  {
    path: '/',
    component: PublicLayout,
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
      },
      {
        path: 'cart',
        name: 'cart',
        component: CartView,
      },
      {
        path: 'tracking/:id?',
        name: 'tracking',
        component: TrackingView,
      },
    ],
  },

  // AUTH ROUTES (Standalone, No Layout)
  {
    path: '/login',
    name: 'login',
    component: LoginView,
  },
  {
    path: '/register',
    name: 'register',
    component: LoginView,
  },

  // CUSTOMER ACCOUNT ROUTES (Auth Required) - Use PublicLayout
  {
    path: '/account',
    component: PublicLayout,
    meta: { requiresAuth: true, role: 'customer' },
    children: [
      {
        path: '',
        name: 'customer-dashboard',
        component: CustomerDashboard,
      },
      {
        path: 'orders',
        name: 'customer-orders',
        component: CustomerDashboard, // Will create separate view later
      },
      {
        path: 'addresses',
        name: 'customer-addresses',
        component: CustomerDashboard, // Will create separate view later
      },
      {
        path: 'profile',
        name: 'customer-profile',
        component: CustomerDashboard, // Will create separate view later
      },
    ],
  },

  // MANAGER ROUTES (Stock Manager Platform)
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
  {
    path: '/admin123',
    component: ManagerLayout,
    meta: { requiresAuth: true, role: 'store_manager' },
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

  // DRIVER ROUTES
  {
    path: '/driver',
    name: 'driver-portal',
    component: DelivererComingSoonView,
    meta: { requiresAuth: true, role: 'driver' },
  },

  // ADMIN ROUTES (General Operations Manager)
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminLoginView,
  },
  {
    path: '/admin',
    redirect: '/admin/dashboard',
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: GeneralManagerView,
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/general-manager',
    redirect: '/admin/dashboard',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

// Navigation Guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const requiredRole = to.meta.role;

  if (requiresAuth) {
    if (!authStore.isAuthenticated) {
      // Redirect to login with return URL
      next({
        path: '/login',
        query: { returnUrl: to.fullPath }
      });
    } else if (requiredRole && authStore.userRole !== requiredRole) {
      // User doesn't have required role, redirect to their appropriate dashboard
      if (authStore.userRole === 'customer') {
        next('/account');
      } else if (authStore.userRole === 'store_manager') {
        next('/admin123/dashboard');
      } else if (authStore.userRole === 'driver') {
        next('/driver');
      } else if (authStore.userRole === 'admin') {
        next('/admin/dashboard');
      } else {
        next('/');
      }
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
