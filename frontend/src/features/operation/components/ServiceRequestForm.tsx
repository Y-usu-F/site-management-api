import { useState } from 'react'
import { useTranslation } from 'react-i18next'
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
  const { t } = useTranslation(['operations', 'common'])
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
        if (!siteId || Number(siteId) <= 0) next.site_id = t('operations.common.validationSiteRequired')
        if (title.trim().length < 3) next.title = t('operations.common.validationTitleMin')
        if (description.trim().length < 3) next.description = t('operations.common.validationDescriptionMin')
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
          label={t('operations.common.site')}
          placeholder={t('operations.common.site')}
          value={siteId}
          onChange={setSiteId}
          queryKey="sites"
          queryFn={listLookupSites}
        />
        <SearchableLookupSelect
          label={t('operations.common.unit')}
          placeholder={t('operations.common.unit')}
          value={unitId}
          onChange={setUnitId}
          queryKey="units"
          queryFn={listLookupUnits}
        />
        <SearchableLookupSelect
          label={t('operations.common.resident')}
          placeholder={t('operations.common.resident')}
          value={residentId}
          onChange={setResidentId}
          queryKey="residents"
          queryFn={listLookupResidents}
        />
        <input value={categoryId} onChange={(e) => setCategoryId(e.target.value)} placeholder={t('operations.common.category')} className="rounded border px-3 py-2 text-sm" />
      </div>
      <input value={title} onChange={(e) => setTitle(e.target.value)} placeholder={t('operations.common.title')} className="w-full rounded border px-3 py-2 text-sm" />
      {errors.title ? <p className="text-xs text-red-600">{errors.title}</p> : null}
      <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder={t('operations.common.description')} className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {errors.description ? <p className="text-xs text-red-600">{errors.description}</p> : null}
      <div className="grid gap-3 sm:grid-cols-2">
        <select value={priority} onChange={(e) => setPriority(e.target.value)} className="rounded border px-3 py-2 text-sm" aria-label={t('operations.common.priority')}>
          <option value="low">{t('operations.common.priorityLow')}</option><option value="normal">{t('operations.common.priorityNormal')}</option><option value="high">{t('operations.common.priorityHigh')}</option><option value="urgent">{t('operations.common.priorityUrgent')}</option>
        </select>
        <select value={source} onChange={(e) => setSource(e.target.value)} className="rounded border px-3 py-2 text-sm" aria-label={t('operations.common.sourcePanel')}>
          <option value="panel">{t('operations.common.sourcePanel')}</option><option value="mobile">{t('operations.common.sourceMobile')}</option><option value="admin">{t('operations.common.sourceAdmin')}</option>
        </select>
      </div>
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? t('common.pleaseWait') : submitLabel}
      </button>
    </form>
  )
}
