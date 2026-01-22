import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true },
    },
    // Operator routes
    {
      path: '/operator',
      meta: { requiresAuth: true, role: 'operator' },
      children: [
        {
          path: 'dashboard',
          name: 'operator-dashboard',
          component: () => import('@/views/operator/DashboardView.vue'),
        },
        {
          path: 'skills',
          name: 'operator-skills',
          component: () => import('@/views/operator/SkillsView.vue'),
        },
        {
          path: 'availability',
          name: 'operator-availability',
          component: () => import('@/views/operator/AvailabilityView.vue'),
        },
        {
          path: 'shifts',
          name: 'operator-shifts',
          component: () => import('@/views/operator/ShiftsView.vue'),
        },
        {
          path: 'marketplace',
          name: 'operator-marketplace',
          component: () => import('@/views/operator/MarketplaceView.vue'),
        },
      ],
    },
    // Client routes
    {
      path: '/client',
      meta: { requiresAuth: true, role: 'client' },
      children: [
        {
          path: 'dashboard',
          name: 'client-dashboard',
          component: () => import('@/views/client/DashboardView.vue'),
        },
        {
          path: 'sites',
          name: 'client-sites',
          component: () => import('@/views/client/SitesView.vue'),
        },
        {
          path: 'sites/:id',
          name: 'client-site-detail',
          component: () => import('@/views/client/SiteDetailView.vue'),
        },
        {
          path: 'jobs',
          name: 'client-jobs',
          component: () => import('@/views/client/JobsView.vue'),
        },
        {
          path: 'jobs/new',
          name: 'client-job-create',
          component: () => import('@/views/client/JobCreateView.vue'),
        },
        {
          path: 'jobs/:id',
          name: 'client-job-detail',
          component: () => import('@/views/client/JobDetailView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Initialize auth state
  if (authStore.isAuthenticated && !authStore.user) {
    await authStore.fetchUser()
  }

  // Check guest routes
  if (to.meta.guest && authStore.isAuthenticated) {
    const redirect = authStore.isOperator ? '/operator/dashboard' : '/client/dashboard'
    return next(redirect)
  }

  // Check protected routes
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Check role-based access
  if (to.meta.role) {
    if (to.meta.role === 'operator' && !authStore.isOperator) {
      return next('/client/dashboard')
    }
    if (to.meta.role === 'client' && !authStore.isClient) {
      return next('/operator/dashboard')
    }
  }

  next()
})

export default router
