import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { Floor } from '@/features/site/types'

const STATUS_OPTIONS = ['active', 'passive'] as const

interface FloorFormProps {
  siteId: number
  blockId: number
  defaultValues?: Partial<Floor>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    block_id: number
    number: number
    label: string
    sort_order: number | null
    status: string
  }) => void
}

export function FloorForm({
  siteId,
  blockId,
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: FloorFormProps) {
  const { t } = useTranslation(['site'])
  const [numberStr, setNumberStr] = useState(
    defaultValues?.number !== undefined ? String(defaultValues.number) : '',
  )
  const [label, setLabel] = useState(defaultValues?.label ?? '')
  const [sortOrder, setSortOrder] = useState(
    defaultValues?.sort_order !== undefined && defaultValues?.sort_order !== null
      ? String(defaultValues.sort_order)
      : '',
  )
  const [status, setStatus] = useState(defaultValues?.status ?? 'active')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})
  const errors = { ...clientErrors, ...serverFieldErrors }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const next: Record<string, string> = {}
    const n = Number(numberStr)
    if (!Number.isFinite(n) || !Number.isInteger(n)) {
      next.number = 'Floor number must be an integer.'
    }
    let sortNum: number | null = null
    if (sortOrder.trim() !== '') {
      const s = Number(sortOrder)
      if (!Number.isFinite(s)) next.sort_order = 'Sort order must be a number.'
      else sortNum = Math.trunc(s)
    }
    setClientErrors(next)
    if (Object.keys(next).length > 0) return
    onSubmit({
      site_id: siteId,
      block_id: blockId,
      number: Math.trunc(Number(numberStr)),
      label: label.trim(),
      sort_order: sortNum,
      status,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
      <div>
        <label htmlFor="floor-number" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('form.floorNumber', { ns: 'site' })}
        </label>
        <input
          id="floor-number"
          type="text"
          inputMode="numeric"
          value={numberStr}
          onChange={(e) => setNumberStr(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.number ? <p className="mt-1 text-xs text-red-600">{errors.number}</p> : null}
      </div>
      <div>
        <label htmlFor="floor-label" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('form.floorLabel', { ns: 'site' })}
        </label>
        <input
          id="floor-label"
          value={label}
          onChange={(e) => setLabel(e.target.value)}
          placeholder={t('form.floorLabel', { ns: 'site' })}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.label ? <p className="mt-1 text-xs text-red-600">{errors.label}</p> : null}
      </div>
      <div>
        <label htmlFor="floor-sort" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('form.sortOrder', { ns: 'site' })}
        </label>
        <input
          id="floor-sort"
          type="text"
          inputMode="numeric"
          value={sortOrder}
          onChange={(e) => setSortOrder(e.target.value)}
          placeholder={t('form.optional', { ns: 'site' })}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.sort_order ? (
          <p className="mt-1 text-xs text-red-600">{errors.sort_order}</p>
        ) : null}
      </div>
      <div>
        <label htmlFor="floor-status" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('status', { ns: 'site' })}
        </label>
        <select
          id="floor-status"
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s}
            </option>
          ))}
        </select>
        {errors.status ? <p className="mt-1 text-xs text-red-600">{errors.status}</p> : null}
      </div>
      <div>
        <button
          type="submit"
          disabled={isSubmitting}
          className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        >
          {isSubmitting ? t('form.saving', { ns: 'site' }) : submitLabel}
        </button>
      </div>
    </form>
  )
}
