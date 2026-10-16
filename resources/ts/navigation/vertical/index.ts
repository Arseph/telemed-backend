import dashboard from './dashboard'

import manage from './manage'

import type { VerticalNavItems } from '@layouts/types'

export default [...dashboard, ...manage] as VerticalNavItems
