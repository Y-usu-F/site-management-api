import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { DuePeriod } from '@/features/finance/types'

const STATUS_OPTIONS = ['draft', 'open', 'closed', 'locked'] as const

interface Props {
  defaultValues?: Partial<DuePeriod>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    period_key: string
    start_date: string
    end_date: string
    due_date: string
    status: string
  }) => void
}

export function DuePeriodForm({
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: Props) {
  const { t } = useTranslation(['finance', 'common'])
  const [siteId, setSiteId] = useState(String(defaultValues?.site_id ?? ''))
  const [periodKey, setPeriodKey] = useState(defaultValues?.period_key ?? '')
  const [startDate, setStartDate] = useState(defaultValues?.start_date ?? '')
  const [endDate, setEndDate] = useState(defaultValues?.end_date ?? '')
  const [dueDate, setDueDate] = useState(defaultValues?.due_date ?? '')
  const [status, setStatus] = useState(defaultValues?.status ?? 'draft')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  return (
    <form
      className="max-w-2xl space-y-4"
      onSubmit={(e) => {
        e.preventDefault()
        const next: Record<string, string> = {}
        const parsedSiteId = Number(siteId)
        if (!Number.isInteger(parsedSiteId) || parsedSiteId <= 0) next.site_id = t('finance.common.validationSiteIdRequiredStrict')
        if (!/^\d{4}-(0[1-9]|1[0-2])$/.test(periodKey)) {
          next.period_key = t('finance.common.validationPeriodKeyFormat')
        }
        if (!startDate) next.start_date = t('finance.common.validationStartDateRequired')
        if (!endDate) next.end_date = t('finance.common.validationEndDateRequired')
        if (!dueDate) next.due_date = t('finance.common.validationDueDateRequired')
        setClientErrors(next)
        if (Object.keys(next).length > 0) return
        onSubmit({
          site_id: parsedSiteId,
          period_key: periodKey,
          start_date: startDate,
          end_date: endDate,
          due_date: dueDate,
          status,
        })
      }}
    >
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">{t('finance.common.siteId')}</label>
          <input
            value={siteId}
            onChange={(e) => setSiteId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.site_id ? <p className="mt-1 text-xs text-red-600">{errors.site_id}</p> : null}
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.periodKey')}</label>
          <input
            placeholder="2026-05"
            value={periodKey}
            onChange={(e) => setPeriodKey(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.period_key ? <p className="mt-1 text-xs text-red-600">{errors.period_key}</p> : null}
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label className="block text-sm font-medium">{t('finance.common.startDate')}</label>
          <input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.start_date ? <p className="mt-1 text-xs text-red-600">{errors.start_date}</p> : null}
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.endDate')}</label>
          <input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.end_date ? <p className="mt-1 text-xs text-red-600">{errors.end_date}</p> : null}
        </div>
        <div>
          <label className="block text-sm font-medium">{t('finance.common.dueDate')}</label>
          <input
            type="date"
            value={dueDate}
            onChange={(e) => setDueDate(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.due_date ? <p className="mt-1 text-xs text-red-600">{errors.due_date}</p> : null}
        </div>
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
