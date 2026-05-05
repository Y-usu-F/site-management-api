import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { listLookupAssets } from '@/features/operation/api/lookupsApi'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function AssetMaintenancePlanForm({
  defaultValues,
  isSubmitting,
  submitLabel,
  onSubmit,
}: Props) {
  const [assetId, setAssetId] = useState(String(defaultValues?.asset_id ?? ''))
  const [frequencyType, setFrequencyType] = useState(String(defaultValues?.frequency_type ?? 'monthly'))
  const [frequencyInterval, setFrequencyInterval] = useState(String(defaultValues?.frequency_interval ?? ''))
  const [nextDueDate, setNextDueDate] = useState(String(defaultValues?.next_due_date ?? ''))
  const [vendorName, setVendorName] = useState(String(defaultValues?.vendor_name ?? ''))
  const [notes, setNotes] = useState(String(defaultValues?.notes ?? ''))
  const [status, setStatus] = useState(String(defaultValues?.status ?? 'active'))
  const assetsQuery = useQuery({ queryKey: ['lookup-assets'], queryFn: listLookupAssets })
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!assetId || Number(assetId) <= 0) {
        setClientError('asset_id zorunlu.')
        return
      }
      if (!nextDueDate) {
        setClientError('next_due_date zorunlu.')
        return
      }
      setClientError(null)
      onSubmit({
        asset_id: Number(assetId),
        frequency_type: frequencyType,
        frequency_interval: frequencyInterval ? Number(frequencyInterval) : undefined,
        next_due_date: nextDueDate,
        vendor_name: vendorName || undefined,
        notes: notes || undefined,
        status,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={assetId} onChange={(e) => setAssetId(e.target.value)} className="rounded border px-3 py-2 text-sm"><option value="">asset_id seciniz</option>{(assetsQuery.data ?? []).map((x) => <option key={x.id} value={x.id}>{x.id} - {x.label}</option>)}</select>
        <input value={frequencyInterval} onChange={(e) => setFrequencyInterval(e.target.value)} placeholder="frequency_interval" className="rounded border px-3 py-2 text-sm" />
        <input type="date" value={nextDueDate} onChange={(e) => setNextDueDate(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input value={vendorName} onChange={(e) => setVendorName(e.target.value)} placeholder="vendor_name" className="rounded border px-3 py-2 text-sm" />
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={frequencyType} onChange={(e) => setFrequencyType(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="daily">daily</option><option value="weekly">weekly</option><option value="monthly">monthly</option><option value="quarterly">quarterly</option><option value="yearly">yearly</option><option value="custom">custom</option>
        </select>
        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="active">active</option><option value="paused">paused</option><option value="cancelled">cancelled</option>
        </select>
      </div>
      <textarea value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="notes" className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
