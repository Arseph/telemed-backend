import { setupLayouts } from 'virtual:generated-layouts'
import type { App } from 'vue'
import type { RouteRecordRaw } from 'vue-router/auto'
import { createRouter, createWebHistory } from 'vue-router/auto'
import LndRoutes from './LndRoutes'
import ManageRoutes from './ManageRoutes'
import PmRoutes from './PmRoutes'
import RnrRoutes from './RnrRoutes'
import RspRoutes from './RspRoutes'

function recursiveLayouts(route: RouteRecordRaw): RouteRecordRaw {
  if (route.children) {
    for (let i = 0; i < route.children.length; i++)
      route.children[i] = recursiveLayouts(route.children[i])

    return route
  }

  return setupLayouts([route])[0]
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),

  scrollBehavior(to) {
    if (to.hash)
      return { el: to.hash, behavior: 'smooth', top: 60 }

    return { top: 0 }
  },
  extendRoutes: pages => [
    ...RspRoutes.map(route => recursiveLayouts(route)),
    ...LndRoutes.map(route => recursiveLayouts(route)),
    ...PmRoutes.map(route => recursiveLayouts(route)),
    ...RnrRoutes.map(route => recursiveLayouts(route)),
    ...ManageRoutes.map(route => recursiveLayouts(route)),
    ...[...pages].map(route => recursiveLayouts(route)),
  ],
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('authToken')

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated)
      next({ path: '/login' })
    else
      next()
  }
  else {
    next()
  }
})
export { router }

export default function (app: App) {
  app.use(router)
}
