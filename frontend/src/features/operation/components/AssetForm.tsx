import { useState } from 'react'
import { listLookupSites, listLookupUnits } from '@/features/operation/api/lookupsApi'
import { SearchableLookupSelect } from '@/features/operation/components/SearchableLookupSelect'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function AssetForm({ defaultValues, isSubmitting, submitLabel, onSubmit }: Props) {
  const [siteId, setSiteId] = useState(String(defaultValues?.site_id ?? ''))
  const [blockId, setBlockId] = useState(String(defaultValues?.block_id ?? ''))
  const [unitId, setUnitId] = useState(String(defaultValues?.unit_id ?? ''))
  const [assetNo, setAssetNo] = useState(String(defaultValues?.asset_no ?? ''))
  const [assetType, setAssetType] = useState(String(defaultValues?.asset_type ?? 'other'))
  const [name, setName] = useState(String(defaultValues?.name ?? ''))
  const [purchaseDate, setPurchaseDate] = useState(String(defaultValues?.purchase_date ?? ''))
  const [status, setStatus] = useState(String(defaultValues?.status ?? 'active'))
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!siteId || Number(siteId) <= 0) {
        setClientError('site_id zorunlu.')
        return
      }
      if (name.trim().length < 2) {
        setClientError('name en az 2 karakter olmali.')
        return
      }
      setClientError(null)
      onSubmit({
        site_id: Number(siteId),
        block_id: blockId ? Number(blockId) : undefined,
        unit_id: unitId ? Number(unitId) : undefined,
        asset_no: assetNo || undefined,
        asset_type: assetType,
        name: name.trim(),
        purchase_date: purchaseDate || undefined,
        status,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <SearchableLookupSelect
          label="Site"
          placeholder="site_id seciniz"
          value={siteId}
          onChange={setSiteId}
          queryKey="sites"
          queryFn={listLookupSites}
        />
        <input value={blockId} onChange={(e) => setBlockId(e.target.value)} placeholder="block_id" className="rounded border px-3 py-2 text-sm" />
        <SearchableLookupSelect
          label="Unit"
          placeholder="unit_id seciniz"
          value={unitId}
          onChange={setUnitId}
          queryKey="units"
          queryFn={listLookupUnits}
        />
        <input value={assetNo} onChange={(e) => setAssetNo(e.target.value)} placeholder="asset_no/code" className="rounded border px-3 py-2 text-sm" />
        <input value={name} onChange={(e) => setName(e.target.value)} placeholder="name" className="rounded border px-3 py-2 text-sm" />
        <input type="date" value={purchaseDate} onChange={(e) => setPurchaseDate(e.target.value)} className="rounded border px-3 py-2 text-sm" />
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={assetType} onChange={(e) => setAssetType(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="elevator">elevator</option><option value="generator">generator</option><option value="camera">camera</option><option value="fire_system">fire_system</option><option value="hydrophore">hydrophore</option><option value="door_system">door_system</option><option value="garden_equipment">garden_equipment</option><option value="cleaning_equipment">cleaning_equipment</option><option value="other">other</option>
        </select>
        <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="active">active</option><option value="maintenance">maintenance</option><option value="broken">broken</option><option value="retired">retired</option>
        </select>
      </div>
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
