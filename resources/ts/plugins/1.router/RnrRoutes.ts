import type { RouteRecordRaw } from 'vue-router/auto'

const RnrRoutes: RouteRecordRaw[] = [
  {
    path: '/rnr',
    name: 'Rnr',
    meta: { requiresAuth: true },
    children: [
      {
        name: 'Praise',
        path: '/rnr/the-best-i-can',
        component: () => import('@/pages/rsp/jobs.vue'),
      },
      {
        name: 'Rewards',
        path: '/lnd/rewards',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
    ],
  },
]

export default RnrRoutes
