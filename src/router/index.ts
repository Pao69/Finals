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
      },
      {
        path: 'admin',
        component: () => import('../views/AdminDashboard.vue'),
        meta: { requiresAdmin: true }
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
  const storage = localStorage.getItem('user') ? localStorage : sessionStorage;
  const token = storage.getItem('token');
  const userData = storage.getItem('user');
  
  // If auth is required and no token/user data
  if (authRequired && (!token || !userData)) {
    // Clear both storages to ensure clean state
    localStorage.clear();
    sessionStorage.clear();
    return next('/login');
  }

  if (userData) {
    const user = JSON.parse(userData);

    // Handle root path redirect
    if (to.path === '/tabs/') {
      return next(user.role === 'admin' ? '/tabs/admin' : '/tabs/dashboard');
    }

    // Check for admin routes
    if (to.matched.some(record => record.meta.requiresAdmin)) {
      if (user.role !== 'admin') {
        return next('/tabs/dashboard');
      }
    }

    // Check for non-admin routes
    if (to.matched.some(record => record.meta.requiresNonAdmin)) {
      if (user.role === 'admin') {
        return next('/tabs/admin');
      }
    }

    // Redirect admin users trying to access regular dashboard
    if (user.role === 'admin' && to.path === '/tabs/dashboard') {
      return next('/tabs/admin');
    }

    // Redirect regular users trying to access admin dashboard
    if (user.role !== 'admin' && to.path === '/tabs/admin') {
      return next('/tabs/dashboard');
    }
  }

  next();
});

export default router