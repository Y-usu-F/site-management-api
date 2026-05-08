import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { listLookupAssets } from '@/features/operation/api/lookupsApi'
import { SearchableLookupSelect } from '@/features/operation/components/SearchableLookupSelect'

interface Props {
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function AssetMaintenanceRecordForm({ isSubmitting, submitLabel, onSubmit }: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const [assetId, setAssetId] = useState('')
  const [maintenancePlanId, setMaintenancePlanId] = useState('')
  const [performedAt, setPerformedAt] = useState('')
  const [costAmount, setCostAmount] = useState('')
  const [currency, setCurrency] = useState('TRY')
  const [description, setDescription] = useState('')
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!assetId || Number(assetId) <= 0) {
        setClientError(t('validationAssetRequired', { ns: 'operations' }))
        return
      }
      if (!performedAt) {
        setClientError(t('validationPerformedRequired', { ns: 'operations' }))
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
        <SearchableLookupSelect
          label={t('assets', { ns: 'operations' })}
          placeholder={t('assets', { ns: 'operations' })}
          value={assetId}
          onChange={setAssetId}
          queryKey="assets"
          queryFn={listLookupAssets}
        />
        <input value={maintenancePlanId} onChange={(e) => setMaintenancePlanId(e.target.value)} placeholder="maintenance_plan_id" className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={performedAt} onChange={(e) => setPerformedAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input value={costAmount} onChange={(e) => setCostAmount(e.target.value)} placeholder={t('cost', { ns: 'operations' })} className="rounded border px-3 py-2 text-sm" />
      </div>
      <input value={currency} onChange={(e) => setCurrency(e.target.value)} placeholder={t('currency', { ns: 'operations' })} className="w-full rounded border px-3 py-2 text-sm" />
      <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder={t('description', { ns: 'operations' })} className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? t('pleaseWait', { ns: 'common' }) : submitLabel}
      </button>
    </form>
  )
}
