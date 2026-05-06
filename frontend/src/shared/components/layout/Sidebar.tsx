import { NavLink } from 'react-router-dom'

const navClass =
  'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-50'

const activeClass =
  'bg-violet-600 text-white hover:bg-violet-600 hover:text-white dark:bg-violet-600 dark:hover:bg-violet-600'

export function Sidebar() {
  return (
    <aside className="flex w-56 shrink-0 flex-col border-r border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
      <div className="border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
        <div className="text-xs font-semibold uppercase tracking-wider text-zinc-500">
          Site management
        </div>
        <div className="text-sm font-semibold text-zinc-900 dark:text-zinc-50">
          Dashboard
        </div>
      </div>
      <nav className="flex flex-1 flex-col gap-0.5 overflow-y-auto p-3">
        <NavLink
          to="/dashboard"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          Overview
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          Site
        </div>
        <NavLink
          to="/sites"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Sites
        </NavLink>
        <NavLink
          to="/residents"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Residents
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          Finance
        </div>
        <NavLink
          to="/finance"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          Dashboard
        </NavLink>
        <NavLink
          to="/finance/due-definitions"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Due definitions
        </NavLink>
        <NavLink
          to="/finance/due-periods"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Due periods
        </NavLink>
        <NavLink
          to="/finance/due-items"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Due items
        </NavLink>
        <NavLink
          to="/finance/payments"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Payments
        </NavLink>
        <NavLink
          to="/finance/deposits"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Deposits
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          Operations
        </div>
        <NavLink
          to="/operations"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
          end
        >
          Dashboard
        </NavLink>
        <NavLink
          to="/operations/service-requests"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Service requests
        </NavLink>
        <NavLink to="/operations/work-orders" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Work orders
        </NavLink>
        <NavLink to="/operations/common-areas" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Common areas
        </NavLink>
        <NavLink to="/operations/common-area-reservations" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Reservations
        </NavLink>
        <NavLink to="/operations/assets" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Assets
        </NavLink>
        <NavLink to="/operations/asset-maintenance-plans" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Maintenance plans
        </NavLink>
        <NavLink to="/operations/asset-maintenance-records" className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}>
          Maintenance records
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          Communication
        </div>
        <NavLink
          to="/communication/announcements"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Announcements
        </NavLink>
        <NavLink
          to="/communication/notifications"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Notifications
        </NavLink>
        <div className="pt-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">
          System
        </div>
        <NavLink
          to="/system/roles"
          className={({ isActive }) => `${navClass} ${isActive ? activeClass : ''}`}
        >
          Roles
        </NavLink>
      </nav>
    </aside>
  )
}
