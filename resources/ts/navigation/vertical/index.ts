import type { VerticalNavItems } from '@layouts/types'
import dashboard from './dashboard'
import lnd from './lnd'
import manage from './manage'
import online from './online'
import pm from './pm'
import rnr from './rnr'
import rsp from './rsp'

export default [...dashboard,...online, ...rsp, ...lnd, ...pm, ...rnr, ...manage] as VerticalNavItems
