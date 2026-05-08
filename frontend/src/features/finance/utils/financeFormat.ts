export function formatMoney(amount: number | string | null | undefined, currency = 'TRY'): string {
  const value = Number(amount ?? 0)
  if (!Number.isFinite(value)) return `0.00 ${currency}`
  return new Intl.NumberFormat('tr-TR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value) + ` ${currency}`
}

export function formatFinanceStatus(status: string | null | undefined): string {
  if (!status) return '-'
  return status
    .replaceAll('_', ' ')
    .split(' ')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

export function formatPaymentMethod(method: string | null | undefined): string {
  const map: Record<string, string> = {
    cash: 'Nakit',
    bank_transfer: 'Havale/EFT',
    credit_card: 'Kredi karti',
    online: 'Online',
  }
  if (!method) return '-'
  return map[method] ?? formatFinanceStatus(method)
}

export function formatDueType(type: string | null | undefined): string {
  const map: Record<string, string> = {
    fixed: 'Sabit',
    unit_area: 'Bagimsiz bolum alani',
    land_share: 'Arsa payi',
    resident_count: 'Sakin sayisi',
  }
  if (!type) return '-'
  return map[type] ?? formatFinanceStatus(type)
}

export function getStatusBadgeVariant(status: string | null | undefined): string {
  const normalized = (status ?? '').toLowerCase()
  if (['paid', 'active', 'open', 'completed', 'received'].includes(normalized)) {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
  }
  if (['partial', 'partially_refunded', 'draft'].includes(normalized)) {
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
  }
  if (['unpaid', 'passive', 'closed', 'locked'].includes(normalized)) {
    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
  }
  if (['cancelled', 'refunded', 'applied_to_debt'].includes(normalized)) {
    return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
  }
  return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
}
