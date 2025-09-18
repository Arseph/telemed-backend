import type { RouteRecordRaw } from 'vue-router/auto'

const PmRoutes: RouteRecordRaw[] = [
  {
    path: '/pm',
    name: 'Pm',
    meta: { requiresAuth: true },
    children: [
      {
        name: 'Opcr',
        path: '/pm/opcr',
        component: () => import('@/pages/rsp/jobs.vue'),
      },
      {
        name: 'Dpcr',
        path: '/pm/dpcr',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
      {
        name: 'Ipcr',
        path: '/pm/ipcr',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
      {
        name: 'SummIp',
        path: '/pm/summary-ipcr',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
    ],
  },
]

export default PmRoutes
