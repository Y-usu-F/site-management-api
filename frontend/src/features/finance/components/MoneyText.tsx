import { formatMoney } from '@/features/finance/utils/financeFormat'

interface Props {
  amount: number | string | null | undefined
  currency?: string
}

export function MoneyText({ amount, currency = 'TRY' }: Props) {
  return <span>{formatMoney(amount, currency)}</span>
}
