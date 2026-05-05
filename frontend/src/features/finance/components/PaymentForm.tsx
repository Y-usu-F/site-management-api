import { useState } from 'react'

import type { LookupResident, LookupUnit, Payment } from '@/features/finance/types'

const METHOD_OPTIONS = ['cash', 'bank_transfer', 'credit_card', 'online'] as const

interface Props {
  defaultValues?: Partial<Payment>
  residents: LookupResident[]
  units: LookupUnit[]
  submitLabel: string
  isSubmitting: boolean
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: {
    site_id: number
    resident_profile_id?: number
    unit_id?: number
    amount: number
    currency: string
    method: string
    payment_date?: string
    description?: string
  }) => void
}

export function PaymentForm({
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
  const [amount, setAmount] = useState(String(defaultValues?.amount ?? ''))
  const [currency, setCurrency] = useState(defaultValues?.currency ?? 'TRY')
  const [method, setMethod] = useState(defaultValues?.method ?? 'cash')
  const [paymentDate, setPaymentDate] = useState(
    (defaultValues?.payment_date ?? '').toString().slice(0, 16),
  )
  const [description, setDescription] = useState(defaultValues?.description ?? '')
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})

  const errors = { ...clientErrors, ...serverFieldErrors }

  return (
    <form
      className="max-w-2xl space-y-4"
      onSubmit={(e) => {
        e.preventDefault()
        const next: Record<string, string> = {}
        const parsedSiteId = Number(siteId)
        if (!Number.isInteger(parsedSiteId) || parsedSiteId <= 0) next.site_id = 'Site id zorunlu.'
        const parsedAmount = Number(amount)
        if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) next.amount = 'Amount 0dan buyuk olmali.'
        setClientErrors(next)
        if (Object.keys(next).length > 0) return
        onSubmit({
          site_id: parsedSiteId,
          resident_profile_id: residentId ? Number(residentId) : undefined,
          unit_id: unitId ? Number(unitId) : undefined,
          amount: parsedAmount,
          currency,
          method,
          payment_date: paymentDate ? paymentDate.replace('T', ' ') + ':00' : undefined,
          description: description.trim() || undefined,
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
          <label className="block text-sm font-medium">Amount</label>
          <input
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
          {errors.amount ? <p className="mt-1 text-xs text-red-600">{errors.amount}</p> : null}
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
        </div>
      </div>
      <div className="grid gap-4 sm:grid-cols-3">
        <div>
          <label className="block text-sm font-medium">Currency</label>
          <input
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
        <div>
          <label className="block text-sm font-medium">Method</label>
          <select
            value={method}
            onChange={(e) => setMethod(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          >
            {METHOD_OPTIONS.map((item) => (
              <option key={item} value={item}>
                {item}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium">Payment date</label>
          <input
            type="datetime-local"
            value={paymentDate}
            onChange={(e) => setPaymentDate(e.target.value)}
            className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
          />
        </div>
      </div>
      <div>
        <label className="block text-sm font-medium">Note</label>
        <textarea
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          className="mt-1 min-h-24 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
        />
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
