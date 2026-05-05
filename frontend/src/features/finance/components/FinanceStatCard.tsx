import { Link } from 'react-router-dom'

interface Props {
  title: string
  value: number | string
  description: string
  to: string
}

export function FinanceStatCard({ title, value, description, to }: Props) {
  return (
    <Link
      to={to}
      className="rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-violet-300 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div className="text-sm text-zinc-500">{title}</div>
      <div className="mt-1 text-2xl font-semibold">{value}</div>
      <div className="mt-1 text-xs text-zinc-500">{description}</div>
    </Link>
  )
}
