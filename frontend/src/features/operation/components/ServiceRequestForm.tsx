import { useState } from 'react'
import { listLookupResidents, listLookupSites, listLookupUnits } from '@/features/operation/api/lookupsApi'
import { SearchableLookupSelect } from '@/features/operation/components/SearchableLookupSelect'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  serverFieldErrors?: Record<string, string>
  onSubmit: (values: Record<string, unknown>) => void
}

export function ServiceRequestForm({
  defaultValues,
  isSubmitting,
  submitLabel,
  serverFieldErrors = {},
  onSubmit,
}: Props) {
  const [siteId, setSiteId] = useState(String(defaultValues?.site_id ?? ''))
  const [unitId, setUnitId] = useState(String(defaultValues?.unit_id ?? ''))
  const [residentId, setResidentId] = useState(String(defaultValues?.resident_profile_id ?? ''))
  const [categoryId, setCategoryId] = useState(String(defaultValues?.category_id ?? ''))
  const [title, setTitle] = useState(String(defaultValues?.title ?? ''))
  const [description, setDescription] = useState(String(defaultValues?.description ?? ''))
  const [priority, setPriority] = useState(String(defaultValues?.priority ?? 'normal'))
  const [source, setSource] = useState(String(defaultValues?.source ?? 'panel'))
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({})
  const errors = { ...clientErrors, ...serverFieldErrors }

  return (
    <form
      className="space-y-4"
      onSubmit={(e) => {
        e.preventDefault()
        const next: Record<string, string> = {}
        if (!siteId || Number(siteId) <= 0) next.site_id = 'Site id zorunlu.'
        if (title.trim().length < 3) next.title = 'Title en az 3 karakter olmali.'
        if (description.trim().length < 3) next.description = 'Description en az 3 karakter olmali.'
        setClientErrors(next)
        if (Object.keys(next).length > 0) return
        onSubmit({
          site_id: Number(siteId),
          unit_id: unitId ? Number(unitId) : undefined,
          resident_profile_id: residentId ? Number(residentId) : undefined,
          category_id: categoryId ? Number(categoryId) : undefined,
          title: title.trim(),
          description: description.trim(),
          priority,
          source,
        })
      }}
    >
      <div className="grid gap-3 sm:grid-cols-2">
        <SearchableLookupSelect
          label="Site"
          placeholder="site_id seciniz"
          value={siteId}
          onChange={setSiteId}
          queryKey="sites"
          queryFn={listLookupSites}
        />
        <SearchableLookupSelect
          label="Unit"
          placeholder="unit_id seciniz"
          value={unitId}
          onChange={setUnitId}
          queryKey="units"
          queryFn={listLookupUnits}
        />
        <SearchableLookupSelect
          label="Resident"
          placeholder="resident_profile_id seciniz"
          value={residentId}
          onChange={setResidentId}
          queryKey="residents"
          queryFn={listLookupResidents}
        />
        <input value={categoryId} onChange={(e) => setCategoryId(e.target.value)} placeholder="category_id" className="rounded border px-3 py-2 text-sm" />
      </div>
      <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder="title" className="w-full rounded border px-3 py-2 text-sm" />
      {errors.title ? <p className="text-xs text-red-600">{errors.title}</p> : null}
      <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder="description" className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {errors.description ? <p className="text-xs text-red-600">{errors.description}</p> : null}
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={priority} onChange={(e) => setPriority(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="low">low</option><option value="normal">normal</option><option value="high">high</option><option value="urgent">urgent</option>
        </select>
        <select value={source} onChange={(e) => setSource(e.target.value)} className="rounded border px-3 py-2 text-sm">
          <option value="panel">panel</option><option value="mobile">mobile</option><option value="admin">admin</option>
        </select>
      </div>
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
