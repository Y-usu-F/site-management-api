import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { listLookupAssets } from '@/features/operation/api/lookupsApi'

interface Props {
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function AssetMaintenanceRecordForm({ isSubmitting, submitLabel, onSubmit }: Props) {
  const [assetId, setAssetId] = useState('')
  const [maintenancePlanId, setMaintenancePlanId] = useState('')
  const [performedAt, setPerformedAt] = useState('')
  const [costAmount, setCostAmount] = useState('')
  const [currency, setCurrency] = useState('TRY')
  const [description, setDescription] = useState('')
  const assetsQuery = useQuery({ queryKey: ['lookup-assets'], queryFn: listLookupAssets })
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!assetId || Number(assetId) <= 0) {
        setClientError('asset_id zorunlu.')
        return
      }
      if (!performedAt) {
        setClientError('performed_at zorunlu.')
        return
      }
      setClientError(null)
      onSubmit({
        asset_id: Number(assetId),
        maintenance_plan_id: maintenancePlanId ? Number(maintenancePlanId) : undefined,
        performed_at: performedAt ? performedAt.replace('T', ' ') + ':00' : undefined,
        cost_amount: costAmount ? Number(costAmount) : undefined,
        currency: currency || undefined,
        description: description || undefined,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={assetId} onChange={(e) => setAssetId(e.target.value)} className="rounded border px-3 py-2 text-sm"><option value="">asset_id seciniz</option>{(assetsQuery.data ?? []).map((x) => <option key={x.id} value={x.id}>{x.id} - {x.label}</option>)}</select>
        <input value={maintenancePlanId} onChange={(e) => setMaintenancePlanId(e.target.value)} placeholder="maintenance_plan_id" className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={performedAt} onChange={(e) => setPerformedAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input value={costAmount} onChange={(e) => setCostAmount(e.target.value)} placeholder="cost_amount" className="rounded border px-3 py-2 text-sm" />
      </div>
      <input value={currency} onChange={(e) => setCurrency(e.target.value)} placeholder="currency" className="w-full rounded border px-3 py-2 text-sm" />
      <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder="description" className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
