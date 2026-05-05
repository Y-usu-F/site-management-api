import { Link } from 'react-router-dom'

const cards = [
  { to: '/operations/service-requests', title: 'Service Requests' },
  { to: '/operations/work-orders', title: 'Work Orders' },
  { to: '/operations/common-areas', title: 'Common Areas' },
  { to: '/operations/common-area-reservations', title: 'Reservations' },
  { to: '/operations/assets', title: 'Assets' },
  { to: '/operations/asset-maintenance-plans', title: 'Maintenance Plans' },
  { to: '/operations/asset-maintenance-records', title: 'Maintenance Records' },
]

export function OperationsDashboardPage() {
  return (
    <div className="space-y-4">
      <h1 className="text-xl font-semibold">Operations</h1>
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        {cards.map((card) => (
          <Link key={card.to} to={card.to} className="rounded-xl border border-zinc-200 bg-white p-4 hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900">
            <div className="text-base font-medium">{card.title}</div>
          </Link>
        ))}
      </div>
    </div>
  )
}
