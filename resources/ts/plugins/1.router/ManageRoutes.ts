import type { RouteRecordRaw } from 'vue-router/auto'

const LndRoutes: RouteRecordRaw[] = [
  {
    path: '/manage',
    name: 'Manage',
    meta: { requiresAuth: true },
    children: [
      {
        name: 'Users',
        path: '/manage/users',
        component: () => import('@/pages/manage/users.vue'),
      },
      {
        name: 'Dtr',
        path: '/manage/dtr-management',
        component: () => import('@/pages/manage/dtrreg.vue'),
      },
      {
        name: 'DtrView',
        path: '/manage/dtr/view/:id',
        component: () => import('@/pages/manage/dtrview.vue'),
      },
    ],
  },
]

export default LndRoutes
