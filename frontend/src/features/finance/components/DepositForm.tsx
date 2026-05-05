import { useState } from 'react'

import type { Deposit, LookupResident, LookupUnit } from '@/features/finance/types'

interface Props {
  defaultValues?: Partial<Deposit>
  residents: LookupResident[]
  units: LookupUnit[]
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    resident_profile_id: number
    unit_id: number
    initial_amount: number
    currency: string
    notes?: string
  }) => void
}

export function DepositForm({
  defaultValues,
  residents,
  units,
  submitLabel,
  isSubmitting,
  serverFieldErrors = {},
  onSubmit,
}: Props) {
  const [siteId, setSiteId] = useState(String(defaultValues?.site_id ?? ''))
  const [residentId, setResidentId] = useState(String(defaultValues?.resident_profile_id ?? ''))
  const [unitId, setUnitId] = useState(String(defaultValues?.unit_id ?? ''))
  const [amount, setAmount] = useState(String(defaultValues?.initial_amount ?? ''))
  const [currency, setCurrency] = useState(defaultValues?.currency ?? 'TRY')
  const [notes, setNotes] = useState(defaultValues?.notes ?? '')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  return (
    <form
      className="max-w-2xl space-y-4"
      onSubmit={(e) => {
        e.preventDefault()
        const next: Record<string, string> = {}
        const parsedSiteId = Number(siteId)
        const parsedResidentId = Number(residentId)
        const parsedUnitId = Number(unitId)
        const parsedAmount = Number(amount)

        if (!Number.isInteger(parsedSiteId) || parsedSiteId <= 0) next.site_id = 'Site id zorunlu.'
        if (!Number.isInteger(parsedResidentId) || parsedResidentId <= 0) {
          next.resident_profile_id = 'Resident seciniz.'
        }
        if (!Number.isInteger(parsedUnitId) || parsedUnitId <= 0) next.unit_id = 'Unit seciniz.'
        if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) next.initial_amount = 'Amount gecersiz.'

        setClientErrors(next)
        if (Object.keys(next).length > 0) return
        onSubmit({
          site_id: parsedSiteId,
          resident_profile_id: parsedResidentId,
          unit_id: parsedUnitId,
          initial_amount: parsedAmount,
          currency: currency.trim() || 'TRY',
          notes: notes.trim() || undefined,
        })
      }}
    >
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">Site id</label>
          <input
            value={siteId}
            onChange={(e) => setSiteId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.site_id ? <p className="mt-1 text-xs text-red-600">{errors.site_id}</p> : null}
        </div>
        <div>
          <label className="block text-sm font-medium">Initial amount</label>
          <input
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.initial_amount ? (
            <p className="mt-1 text-xs text-red-600">{errors.initial_amount}</p>
          ) : null}
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">Resident</label>
          <select
            value={residentId}
            onChange={(e) => setResidentId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          >
            <option value="">Seciniz</option>
            {residents.map((resident) => (
              <option key={resident.id} value={resident.id}>
                {resident.id} - {(resident.first_name ?? '').trim()} {(resident.last_name ?? '').trim()}
              </option>
            ))}
          </select>
          {errors.resident_profile_id ? (
            <p className="mt-1 text-xs text-red-600">{errors.resident_profile_id}</p>
          ) : null}
        </div>
        <div>
          <label className="block text-sm font-medium">Unit</label>
          <select
            value={unitId}
            onChange={(e) => setUnitId(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          >
            <option value="">Seciniz</option>
            {units.map((unit) => (
              <option key={unit.id} value={unit.id}>
                {unit.id} - {unit.unit_no ?? '-'}
              </option>
            ))}
          </select>
          {errors.unit_id ? <p className="mt-1 text-xs text-red-600">{errors.unit_id}</p> : null}
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div>
          <label className="block text-sm font-medium">Currency</label>
          <input
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Notes</label>
          <input
            value={notes}
            onChange={(e) => setNotes(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
      </div>
      <button
        type="submit"
        disabled={isSubmitting}
        className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
      >
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
