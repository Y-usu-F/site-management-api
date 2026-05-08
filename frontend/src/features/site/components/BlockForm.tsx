import { useState } from 'react'
import { useTranslation } from 'react-i18next'

import type { Block } from '@/features/site/types'

const STATUS_OPTIONS = ['active', 'passive'] as const

interface BlockFormProps {
  siteId: number
  defaultValues?: Partial<Block>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    name: string
    code: string
    sort_order: number | null
    status: string
  }) => void
}

export function BlockForm({
  siteId,
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: BlockFormProps) {
  const { t } = useTranslation(['site'])
  const [name, setName] = useState(defaultValues?.name ?? '')
  const [code, setCode] = useState(defaultValues?.code ?? '')
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
    if (name.trim().length < 1) next.name = 'Name is required.'
    if (code.trim().length < 1) next.code = 'Code is required.'
    let sortNum: number | null = null
    if (sortOrder.trim() !== '') {
      const n = Number(sortOrder)
      if (!Number.isFinite(n)) next.sort_order = 'Sort order must be a number.'
      else sortNum = Math.trunc(n)
    }
    setClientErrors(next)
    if (Object.keys(next).length > 0) return
    onSubmit({
      site_id: siteId,
      name: name.trim(),
      code: code.trim(),
      sort_order: sortNum,
      status,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
      <div>
        <label htmlFor="block-name" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('form.name', { ns: 'site' })}
        </label>
        <input
          id="block-name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.name ? <p className="mt-1 text-xs text-red-600">{errors.name}</p> : null}
      </div>
      <div>
        <label htmlFor="block-code" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('form.code', { ns: 'site' })}
        </label>
        <input
          id="block-code"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.code ? <p className="mt-1 text-xs text-red-600">{errors.code}</p> : null}
      </div>
      <div>
        <label
          htmlFor="block-sort"
          className="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
        >
          {t('form.sortOrder', { ns: 'site' })}
        </label>
        <input
          id="block-sort"
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
        <label htmlFor="block-status" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {t('status', { ns: 'site' })}
        </label>
        <select
          id="block-status"
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
