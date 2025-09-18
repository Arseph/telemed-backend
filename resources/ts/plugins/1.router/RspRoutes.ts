import type { RouteRecordRaw } from 'vue-router/auto'

const RspRoutes: RouteRecordRaw[] = [
  {
    path: '/rsp',
    name: 'Rsp',
    meta: { requiresAuth: true },
    children: [
      {
        name: 'JobPortal',
        path: '/rsp/job-portal',
        component: () => import('@/pages/rsp/jobs.vue'),
      },
      {
        name: 'Evaluation',
        path: '/rsp/evaluation',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
      {
        name: 'ExitInterview',
        path: '/rsp/exit-interview',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
    ],
  },
]

export default RspRoutes
