import dashboard from './dashboard'
import lnd from './lnd'
import manage from './manage'
import pm from './pm'
import rnr from './rnr'
import rsp from './rsp'
import type { VerticalNavItems } from '@layouts/types'

export default [...dashboard, ...rsp, ...lnd, ...pm, ...rnr, ...manage] as VerticalNavItems
