import { useState } from 'react'

import type { Site } from '@/features/site/types'

const STATUS_OPTIONS = ['active', 'passive'] as const

interface SiteFormProps {
  defaultValues?: Partial<Site>
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    name: string
    code: string
    address: string
    status: string
  }) => void
}

export function SiteForm({
  defaultValues,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: SiteFormProps) {
  const [name, setName] = useState(defaultValues?.name ?? '')
  const [code, setCode] = useState(defaultValues?.code ?? '')
  const [address, setAddress] = useState(defaultValues?.address ?? '')
  const [status, setStatus] = useState(defaultValues?.status ?? 'active')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const next: Record<string, string> = {}
    if (name.trim().length < 2) next.name = 'Name must be at least 2 characters.'
    if (code.trim().length < 1) next.code = 'Code is required.'
    setClientErrors(next)
    if (Object.keys(next).length > 0) return
    onSubmit({
      name: name.trim(),
      code: code.trim(),
      address: address.trim(),
      status,
    })
  }

  return (
    <form onSubmit={handleSubmit} className="max-w-xl space-y-4">
      <div>
        <label htmlFor="site-name" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Name
        </label>
        <input
          id="site-name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          autoComplete="off"
        />
        {errors.name ? <p className="mt-1 text-xs text-red-600">{errors.name}</p> : null}
      </div>
      <div>
        <label htmlFor="site-code" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Code
        </label>
        <input
          id="site-code"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-mono dark:border-zinc-600 dark:bg-zinc-950"
          autoComplete="off"
        />
        {errors.code ? <p className="mt-1 text-xs text-red-600">{errors.code}</p> : null}
      </div>
      <div>
        <label htmlFor="site-address" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Address
        </label>
        <textarea
          id="site-address"
          value={address}
          onChange={(e) => setAddress(e.target.value)}
          rows={3}
          className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
        {errors.address ? <p className="mt-1 text-xs text-red-600">{errors.address}</p> : null}
      </div>
      <div>
        <label htmlFor="site-status" className="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
          Status
        </label>
        <select
          id="site-status"
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
          {isSubmitting ? 'Saving…' : submitLabel}
        </button>
      </div>
    </form>
  )
}
