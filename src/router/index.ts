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
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/dashboard'
      },
      {
        path: 'dashboard',
        component: () => import('../views/DashboardPage.vue')
      },
      {
        path: 'tasks',
        component: TasksPage
      },
      {
        path: 'tasks/add',
        component: () => import('../views/AddTaskPage.vue')
      },
      {
        path: 'tasks/edit/:id',
        component: () => import('../views/EditTaskPage.vue')
      },
      {
        path: 'tasks/view/:id',
        component: () => import('../views/TaskView.vue')
      },
      {
        path: 'resources',
        component: () => import('../views/ResourcesPage.vue')
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

// Navigation guard to check authentication
router.beforeEach((to, from, next) => {
  const publicPages = ['/login', '/signup'];
  const authRequired = !publicPages.includes(to.path);
  
  // Check both localStorage and sessionStorage for authentication
  const loggedInPermanent = localStorage.getItem('user');
  const loggedInSession = sessionStorage.getItem('user');
  const loggedIn = loggedInPermanent || loggedInSession;

  if (authRequired && !loggedIn) {
    return next('/login');
  }

  next();
});

export default router