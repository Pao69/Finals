import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue'
import TasksPage from '../views/TasksPage.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/login'
  },
  {
    path: '/login',
    component: () => import('../views/LoginPage.vue')
  },
  {
    path: '/signup',
    component: () => import('../views/SignupPage.vue')
  },
  {
    path: '/forgot-password',
    component: () => import('../views/ForgotPasswordPage.vue')
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: to => {
          const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
          const user = userData ? JSON.parse(userData) : null;
          return user?.role === 'admin' ? '/tabs/admin' : '/tabs/dashboard';
        }
      },
      {
        path: 'admin',
        component: () => import('../views/AdminDashboard.vue'),
        meta: { requiresAdmin: true }
      },
      {
        path: 'dashboard',
        component: () => import('../views/DashboardPage.vue'),
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'tasks',
        component: TasksPage,
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'tasks/add',
        component: () => import('../views/AddTaskPage.vue'),
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'tasks/edit/:id',
        component: () => import('../views/EditTaskPage.vue'),
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'tasks/view/:id',
        component: () => import('../views/TaskView.vue'),
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'resources',
        component: () => import('../views/ResourcesPage.vue'),
        meta: { requiresNonAdmin: true }
      },
      {
        path: 'settings',
        component: () => import('../views/SettingsPage.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guard to check authentication and roles
router.beforeEach((to, from, next) => {
  const publicPages = ['/login', '/signup', '/forgot-password'];
  const authRequired = !publicPages.includes(to.path);
  
  // Get user data and token from the appropriate storage
  const userData = localStorage.getItem('user') || sessionStorage.getItem('user');
  const token = localStorage.getItem('token') || sessionStorage.getItem('token');
  
  // If auth is required and no token/user data
  if (authRequired && (!token || !userData)) {
    // Clear both storages to ensure clean state
    localStorage.clear();
    sessionStorage.clear();
    return next('/login');
  }

  if (userData) {
    const user = JSON.parse(userData);
    const isAdmin = user.role === 'admin';

    // Force admin users to admin route and non-admin users to dashboard
    if (isAdmin && to.path === '/tabs/dashboard') {
      return next('/tabs/admin');
    }
    if (!isAdmin && to.path === '/tabs/admin') {
      return next('/tabs/dashboard');
    }

    // Handle root paths
    if (to.path === '/tabs/' || to.path === '/tabs') {
      return next(isAdmin ? '/tabs/admin' : '/tabs/dashboard');
    }
    
    // Check for admin routes
    if (to.matched.some(record => record.meta.requiresAdmin)) {
      if (!isAdmin) {
        return next('/tabs/dashboard');
      }
    }

    // Check for non-admin routes
    if (to.matched.some(record => record.meta.requiresNonAdmin)) {
      if (isAdmin) {
        return next('/tabs/admin');
      }
    }
  }

  next();
});

export default router