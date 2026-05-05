function getStatusClasses(status: string): string {
  const normalized = status.trim().toLowerCase()
  if (['active', 'approved', 'completed', 'closed', 'resolved'].includes(normalized)) {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
  }
  if (['pending', 'open', 'assigned', 'in_progress', 'maintenance', 'paused'].includes(normalized)) {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
  }
  if (['rejected', 'cancelled', 'broken', 'retired'].includes(normalized)) {
    return 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
  }
  return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
}

interface OperationStatusBadgeProps {
  status?: string | null
}

export function OperationStatusBadge({ status }: OperationStatusBadgeProps) {
  const safeStatus = status?.trim() || '-'
  return (
    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusClasses(safeStatus)}`}>
      {safeStatus}
    </span>
  )
}

