import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { listLookupCommonAreas, listLookupResidents, listLookupUnits } from '@/features/operation/api/lookupsApi'

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
  const [commonAreaId, setCommonAreaId] = useState(String(defaultValues?.common_area_id ?? ''))
  const [residentId, setResidentId] = useState(String(defaultValues?.resident_profile_id ?? ''))
  const [unitId, setUnitId] = useState(String(defaultValues?.unit_id ?? ''))
  const [startAt, setStartAt] = useState(String(defaultValues?.start_at ?? '').replace(' ', 'T').slice(0, 16))
  const [endAt, setEndAt] = useState(String(defaultValues?.end_at ?? '').replace(' ', 'T').slice(0, 16))
  const [participantCount, setParticipantCount] = useState(String(defaultValues?.participant_count ?? ''))
  const [notes, setNotes] = useState(String(defaultValues?.notes ?? ''))
  const [clientError, setClientError] = useState<string | null>(null)
  const commonAreasQuery = useQuery({ queryKey: ['lookup-common-areas'], queryFn: listLookupCommonAreas })
  const residentsQuery = useQuery({ queryKey: ['lookup-residents'], queryFn: listLookupResidents })
  const unitsQuery = useQuery({ queryKey: ['lookup-units'], queryFn: listLookupUnits })

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!commonAreaId || Number(commonAreaId) <= 0) {
        setClientError('common_area_id zorunlu.')
        return
      }
      if (!startAt || !endAt) {
        setClientError('start_at ve end_at zorunlu.')
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
        <select value={commonAreaId} onChange={(e) => setCommonAreaId(e.target.value)} className="rounded border px-3 py-2 text-sm"><option value="">common_area_id seciniz</option>{(commonAreasQuery.data ?? []).map((x) => <option key={x.id} value={x.id}>{x.id} - {x.label}</option>)}</select>
        <select value={residentId} onChange={(e) => setResidentId(e.target.value)} className="rounded border px-3 py-2 text-sm"><option value="">resident_profile_id seciniz</option>{(residentsQuery.data ?? []).map((x) => <option key={x.id} value={x.id}>{x.id} - {x.label}</option>)}</select>
        <select value={unitId} onChange={(e) => setUnitId(e.target.value)} className="rounded border px-3 py-2 text-sm"><option value="">unit_id seciniz</option>{(unitsQuery.data ?? []).map((x) => <option key={x.id} value={x.id}>{x.id} - {x.label}</option>)}</select>
        <input value={participantCount} onChange={(e) => setParticipantCount(e.target.value)} placeholder="participant_count" className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={startAt} onChange={(e) => setStartAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={endAt} onChange={(e) => setEndAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
      </div>
      <textarea value={notes} onChange={(e) => setNotes(e.target.value)} placeholder="notes" className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? 'Saving…' : submitLabel}
      </button>
    </form>
  )
}
