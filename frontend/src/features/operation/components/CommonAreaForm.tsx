import { useState } from 'react'
import { useTranslation } from 'react-i18next'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function CommonAreaForm({ defaultValues, isSubmitting, submitLabel, onSubmit }: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const [siteId, setSiteId] = useState(String(defaultValues?.site_id ?? ''))
  const [name, setName] = useState(String(defaultValues?.name ?? ''))
  const [code, setCode] = useState(String(defaultValues?.code ?? ''))
  const [description, setDescription] = useState(String(defaultValues?.description ?? ''))
  const [capacity, setCapacity] = useState(String(defaultValues?.capacity ?? ''))
  const [status, setStatus] = useState(String(defaultValues?.status ?? 'active'))
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!siteId || Number(siteId) <= 0) {
        setClientError(t('operations.common.validationSiteRequired'))
        return
      }
      if (name.trim().length < 2) {
        setClientError(t('operations.common.validationNameMin'))
        return
      }
      setClientError(null)
      onSubmit({
        site_id: Number(siteId),
        name: name.trim(),
        code: code || undefined,
        description: description || undefined,
        capacity: capacity ? Number(capacity) : undefined,
        status,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <input value={siteId} onChange={(e) => setSiteId(e.target.value)} placeholder={t('operations.common.site')} className="rounded border px-3 py-2 text-sm" />
        <input value={name} onChange={(e) => setName(e.target.value)} placeholder={t('operations.common.name')} className="rounded border px-3 py-2 text-sm" />
        <input value={code} onChange={(e) => setCode(e.target.value)} placeholder={t('operations.common.code')} className="rounded border px-3 py-2 text-sm" />
        <input value={capacity} onChange={(e) => setCapacity(e.target.value)} placeholder={t('operations.common.capacity')} className="rounded border px-3 py-2 text-sm" />
      </div>
      <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder={t('operations.common.description')} className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded border px-3 py-2 text-sm">
        <option value="active">active</option>
        <option value="passive">passive</option>
      </select>
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? t('common.pleaseWait') : submitLabel}
      </button>
    </form>
  )
}
