import type { RouteRecordRaw } from 'vue-router/auto'

const OnlineRoutes: RouteRecordRaw[] = [
  {
    path: '/online/online',
    name: 'online',
    meta: { requiresAuth: true },
    component: () => import('@/pages/online/online.vue'),
  },
]

export default OnlineRoutes
