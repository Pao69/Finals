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
          // Check localStorage first, then sessionStorage
          let userData = localStorage.getItem('user');
          if (!userData) {
            userData = sessionStorage.getItem('user');
          }
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
  console.log('Navigation started:', { from: from.path, to: to.path });
  
  const publicPages = ['/login', '/signup', '/forgot-password'];
  const authRequired = !publicPages.includes(to.path);
  
  // Check if this is a page refresh - only true for initial page load
  const isPageRefresh = !from.name && !from.path;

  console.log('Route transition details:', {
    fromName: from.name,
    fromPath: from.path,
    toName: to.name,
    toPath: to.path,
    isPageRefresh
  });

  // Check both storages
  const localStorageData = {
    token: localStorage.getItem('token'),
    user: localStorage.getItem('user')
  };
  
  const sessionStorageData = {
    token: sessionStorage.getItem('token'),
    user: sessionStorage.getItem('user')
  };

  console.log('Storage state:', {
    localStorage: { hasToken: !!localStorageData.token, hasUser: !!localStorageData.user },
    sessionStorage: { hasToken: !!sessionStorageData.token, hasUser: !!sessionStorageData.user },
    isPageRefresh
  });

  // Determine which storage to use
  let userData = null;
  let token = null;

  if (localStorageData.token && localStorageData.user) {
    // Use localStorage if it has data
    userData = localStorageData.user;
    token = localStorageData.token;
    console.log('Using localStorage data');
  } else if (sessionStorageData.token && sessionStorageData.user) {
    // Use sessionStorage data
    userData = sessionStorageData.user;
    token = sessionStorageData.token;
    console.log('Using sessionStorage data');
    
    // Only clear session on actual browser refresh
    if (isPageRefresh && !publicPages.includes(to.path)) {
      console.log('Page refresh detected with sessionStorage, clearing session');
      sessionStorage.clear();
      return next('/login');
    }
  }

  // Handle authentication requirement
  if (authRequired && (!token || !userData)) {
    console.log('Authentication required but no valid credentials found');
    localStorage.clear();
    sessionStorage.clear();
    return next('/login');
  }

  if (userData) {
    const user = JSON.parse(userData);
    const isAdmin = user.role === 'admin';

    console.log('User authenticated:', { role: user.role, isAdmin });

    // Handle routing based on user role
    if (isAdmin && to.path === '/tabs/dashboard') {
      return next('/tabs/admin');
    }
    if (!isAdmin && to.path === '/tabs/admin') {
      return next('/tabs/dashboard');
    }

    if (to.path === '/tabs/' || to.path === '/tabs') {
      const targetPath = isAdmin ? '/tabs/admin' : '/tabs/dashboard';
      return next(targetPath);
    }
    
    if (to.matched.some(record => record.meta.requiresAdmin) && !isAdmin) {
      return next('/tabs/dashboard');
    }

    if (to.matched.some(record => record.meta.requiresNonAdmin) && isAdmin) {
      return next('/tabs/admin');
    }
  }

  console.log('Navigation proceeding to:', to.path);
  next();
});

export default router