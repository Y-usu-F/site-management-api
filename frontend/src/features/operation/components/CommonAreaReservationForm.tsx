import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { listLookupCommonAreas, listLookupResidents, listLookupUnits } from '@/features/operation/api/lookupsApi'
import { SearchableLookupSelect } from '@/features/operation/components/SearchableLookupSelect'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function CommonAreaReservationForm({
  defaultValues,
  isSubmitting,
  submitLabel,
  onSubmit,
}: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const [commonAreaId, setCommonAreaId] = useState(String(defaultValues?.common_area_id ?? ''))
  const [residentId, setResidentId] = useState(String(defaultValues?.resident_profile_id ?? ''))
  const [unitId, setUnitId] = useState(String(defaultValues?.unit_id ?? ''))
  const [startAt, setStartAt] = useState(String(defaultValues?.start_at ?? '').replace(' ', 'T').slice(0, 16))
  const [endAt, setEndAt] = useState(String(defaultValues?.end_at ?? '').replace(' ', 'T').slice(0, 16))
  const [participantCount, setParticipantCount] = useState(String(defaultValues?.participant_count ?? ''))
  const [notes, setNotes] = useState(String(defaultValues?.notes ?? ''))
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!commonAreaId || Number(commonAreaId) <= 0) {
        setClientError(t('validationCommonAreaRequired', { ns: 'operations' }))
        return
      }
      if (!startAt || !endAt) {
        setClientError(t('validationDateRangeRequired', { ns: 'operations' }))
        return
      }
      setClientError(null)
      onSubmit({
        common_area_id: Number(commonAreaId),
        resident_profile_id: residentId ? Number(residentId) : undefined,
        unit_id: unitId ? Number(unitId) : undefined,
        start_at: startAt ? startAt.replace('T', ' ') + ':00' : undefined,
        end_at: endAt ? endAt.replace('T', ' ') + ':00' : undefined,
        participant_count: participantCount ? Number(participantCount) : undefined,
        notes: notes || undefined,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <SearchableLookupSelect
          label={t('commonAreas', { ns: 'operations' })}
          placeholder={t('commonAreas', { ns: 'operations' })}
          value={commonAreaId}
          onChange={setCommonAreaId}
          queryKey="common-areas"
          queryFn={listLookupCommonAreas}
        />
        <SearchableLookupSelect
          label={t('resident', { ns: 'operations' })}
          placeholder={t('resident', { ns: 'operations' })}
          value={residentId}
          onChange={setResidentId}
          queryKey="residents"
          queryFn={listLookupResidents}
        />
        <SearchableLookupSelect
          label={t('unit', { ns: 'operations' })}
          placeholder={t('unit', { ns: 'operations' })}
          value={unitId}
          onChange={setUnitId}
          queryKey="units"
          queryFn={listLookupUnits}
        />
        <input value={participantCount} onChange={(e) => setParticipantCount(e.target.value)} placeholder={t('participantCount', { ns: 'operations' })} className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={startAt} onChange={(e) => setStartAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={endAt} onChange={(e) => setEndAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
      </div>
      <textarea value={notes} onChange={(e) => setNotes(e.target.value)} placeholder={t('notes', { ns: 'operations' })} className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? t('pleaseWait', { ns: 'common' }) : submitLabel}
      </button>
    </form>
  )
}
