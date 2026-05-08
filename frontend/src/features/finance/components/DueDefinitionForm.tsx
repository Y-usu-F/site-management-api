import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { DueDefinition } from '@/features/finance/types'

const CALCULATION_OPTIONS = ['fixed', 'unit_area', 'land_share', 'resident_count'] as const
const STATUS_OPTIONS = ['active', 'passive'] as const

interface Props {
  defaultValues?: Partial<DueDefinition>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    name: string
    code?: string
    calculation_type: string
    amount: number
    currency: string
    status: string
  }) => void
}

export function DueDefinitionForm({
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: Props) {
  const { t } = useTranslation(['finance', 'common'])
  const [name, setName] = useState(defaultValues?.name ?? '')
  const [code, setCode] = useState(defaultValues?.code ?? '')
  const [calculationType, setCalculationType] = useState(defaultValues?.calculation_type ?? 'fixed')
  const [amount, setAmount] = useState(String(defaultValues?.amount ?? ''))
  const [currency, setCurrency] = useState(defaultValues?.currency ?? 'TRY')
  const [status, setStatus] = useState(defaultValues?.status ?? 'active')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  return (
    <form
      className="max-w-2xl space-y-4"
      onSubmit={(e) => {
        e.preventDefault()
        const next: Record<string, string> = {}
        if (name.trim().length < 2) next.name = t('finance.common.validationNameMinLength')
        const parsedAmount = Number(amount)
        if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) next.amount = t('finance.common.validationAmountPositive')
        setClientErrors(next)
        if (Object.keys(next).length > 0) return
        onSubmit({
          name: name.trim(),
          code: code.trim() || undefined,
          calculation_type: calculationType,
          amount: parsedAmount,
          currency: currency.trim() || 'TRY',
          status,
        })
      }}
    >
      <div>
          <label className="block text-sm font-medium">{t('finance.common.name')}</label>
        <input
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.name ? <p className="mt-1 text-xs text-red-600">{errors.name}</p> : null}
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">{t('finance.common.code')}</label>
          <input
            value={code}
            onChange={(e) => setCode(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.calculationType')}</label>
          <select
            value={calculationType}
            onChange={(e) => setCalculationType(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          >
            {CALCULATION_OPTIONS.map((item) => (
              <option key={item} value={item}>
                {item}
              </option>
            ))}
          </select>
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label className="block text-sm font-medium">{t('finance.common.amount')}</label>
          <input
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.amount ? <p className="mt-1 text-xs text-red-600">{errors.amount}</p> : null}
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.currency')}</label>
          <input
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.status')}</label>
          <select
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          >
            {STATUS_OPTIONS.map((item) => (
              <option key={item} value={item}>
                {item}
              </option>
            ))}
          </select>
        </div>
      </div>
      <button
        type="submit"
        disabled={isSubmitting}
        className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >
        {isSubmitting ? t('common.pleaseWait') : submitLabel}
      </button>
    </form>
  )
}
