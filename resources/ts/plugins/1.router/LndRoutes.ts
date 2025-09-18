import type { RouteRecordRaw } from 'vue-router/auto'

const LndRoutes: RouteRecordRaw[] = [
  {
    path: '/lnd',
    name: 'Lnd',
    meta: { requiresAuth: true },
    children: [
      {
        name: 'EventsAct',
        path: '/lnd/events-activities',
        component: () => import('@/pages/rsp/jobs.vue'),
      },
      {
        name: 'ComAss',
        path: '/lnd/competency-assessment',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
      {
        name: 'CoachMent',
        path: '/lnd/coaching-mentoring',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
      {
        name: 'AssWork',
        path: '/lnd/assessment-workplace',
        component: () => import('@/pages/rsp/evaluation.vue'),
      },
    ],
  },
]

export default LndRoutes
