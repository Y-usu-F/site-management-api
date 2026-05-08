import { NavLink } from 'react-router-dom'
import { useTranslation } from 'react-i18next'

const navClass =
  'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50'

const activeClass =
  'bg-violet-600 text-white hover:bg-violet-600 hover:text-white dark:bg-violet-600 dark:hover:bg-violet-600'

export function Sidebar() {
  const { t } = useTranslation(['navigation'])

  return (
    <aside className="flex w-56 shrink-0 flex-col border-r border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
      <div className="border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
          {t('appName')}
        </div>
        <div className="text-sm font-semibold text-zinc-900 dark:text-zinc-50">
          {t('dashboard')}
        </div>
      </div>
      <nav className="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
        <NavLink
          to="/dashboard"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          {t('overview')}
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          {t('site')}
        </div>
        <NavLink
          to="/sites"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('sites')}
        </NavLink>
        <NavLink
          to="/residents"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('residents')}
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          {t('finance')}
        </div>
        <NavLink
          to="/finance"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          {t('dashboard')}
        </NavLink>
        <NavLink
          to="/finance/due-definitions"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('dueDefinitions')}
        </NavLink>
        <NavLink
          to="/finance/due-periods"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('duePeriods')}
        </NavLink>
        <NavLink
          to="/finance/due-items"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('dueItems')}
        </NavLink>
        <NavLink
          to="/finance/payments"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('payments')}
        </NavLink>
        <NavLink
          to="/finance/deposits"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('deposits')}
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          {t('operations')}
        </div>
        <NavLink
          to="/operations"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          {t('dashboard')}
        </NavLink>
        <NavLink
          to="/operations/service-requests"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('serviceRequests')}
        </NavLink>
        <NavLink to="/operations/work-orders" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('workOrders')}
        </NavLink>
        <NavLink to="/operations/common-areas" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('commonAreas')}
        </NavLink>
        <NavLink to="/operations/common-area-reservations" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('reservations')}
        </NavLink>
        <NavLink to="/operations/assets" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('assets')}
        </NavLink>
        <NavLink to="/operations/asset-maintenance-plans" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('maintenancePlans')}
        </NavLink>
        <NavLink to="/operations/asset-maintenance-records" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          {t('maintenanceRecords')}
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          {t('communication')}
        </div>
        <NavLink
          to="/communication/announcements"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('announcements')}
        </NavLink>
        <NavLink
          to="/communication/notifications"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('notifications')}
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          {t('system')}
        </div>
        <NavLink
          to="/system/roles"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          {t('roles')}
        </NavLink>
      </nav>
    </aside>
  )
}
