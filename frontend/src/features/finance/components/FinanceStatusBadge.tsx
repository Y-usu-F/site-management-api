import { formatFinanceStatus, getStatusBadgeVariant } from '@/features/finance/utils/financeFormat'

interface Props {
  status: string | null | undefined
}

export function FinanceStatusBadge({ status }: Props) {
  return (
    <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${getStatusBadgeVariant(status)}`}>
      {formatFinanceStatus(status)}
    </span>
  )
}
