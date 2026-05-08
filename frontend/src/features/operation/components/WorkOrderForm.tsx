import { useState } from 'react'
import { useTranslation } from 'react-i18next'

interface Props {
  defaultValues?: Record<string, unknown>
  isSubmitting: boolean
  submitLabel: string
  onSubmit: (values: Record<string, unknown>) => void
}

export function WorkOrderForm({ defaultValues, isSubmitting, submitLabel, onSubmit }: Props) {
  const { t } = useTranslation(['operations', 'common'])
  const [serviceRequestId, setServiceRequestId] = useState(String(defaultValues?.service_request_id ?? ''))
  const [assignedToUserId, setAssignedToUserId] = useState(String(defaultValues?.assigned_to_user_id ?? ''))
  const [vendorName, setVendorName] = useState(String(defaultValues?.vendor_name ?? ''))
  const [plannedStartAt, setPlannedStartAt] = useState(String(defaultValues?.planned_start_at ?? '').replace(' ', 'T').slice(0, 16))
  const [plannedEndAt, setPlannedEndAt] = useState(String(defaultValues?.planned_end_at ?? '').replace(' ', 'T').slice(0, 16))
  const [costAmount, setCostAmount] = useState(String(defaultValues?.cost_amount ?? ''))
  const [currency, setCurrency] = useState(String(defaultValues?.currency ?? 'TRY'))
  const [notes, setNotes] = useState(String(defaultValues?.notes ?? ''))
  const [clientError, setClientError] = useState<string | null>(null)

  return (
    <form className="space-y-4" onSubmit={(e) => {
      e.preventDefault()
      if (!serviceRequestId || Number(serviceRequestId) <= 0) {
        setClientError(t('operations.common.validationServiceRequestRequired'))
        return
      }
      setClientError(null)
      onSubmit({
        service_request_id: Number(serviceRequestId),
        assigned_to_user_id: assignedToUserId ? Number(assignedToUserId) : undefined,
        vendor_name: vendorName || undefined,
        planned_start_at: plannedStartAt ? plannedStartAt.replace('T', ' ') + ':00' : undefined,
        planned_end_at: plannedEndAt ? plannedEndAt.replace('T', ' ') + ':00' : undefined,
        cost_amount: costAmount ? Number(costAmount) : undefined,
        currency: currency || undefined,
        notes: notes || undefined,
      })
    }}>
      <div className="grid gap-3 sm:grid-cols-2">
        <input value={serviceRequestId} onChange={(e) => setServiceRequestId(e.target.value)} placeholder={t('operations.common.serviceRequest')} className="rounded border px-3 py-2 text-sm" />
        <input value={assignedToUserId} onChange={(e) => setAssignedToUserId(e.target.value)} placeholder={t('operations.common.assignedUser')} className="rounded border px-3 py-2 text-sm" />
        <input value={vendorName} onChange={(e) => setVendorName(e.target.value)} placeholder={t('operations.common.vendor')} className="rounded border px-3 py-2 text-sm" />
        <input value={costAmount} onChange={(e) => setCostAmount(e.target.value)} placeholder={t('operations.common.cost')} className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={plannedStartAt} onChange={(e) => setPlannedStartAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
        <input type="datetime-local" value={plannedEndAt} onChange={(e) => setPlannedEndAt(e.target.value)} className="rounded border px-3 py-2 text-sm" />
      </div>
      <input value={currency} onChange={(e) => setCurrency(e.target.value)} placeholder={t('operations.common.currency')} className="w-full rounded border px-3 py-2 text-sm" />
      <textarea value={notes} onChange={(e) => setNotes(e.target.value)} placeholder={t('operations.common.notes')} className="min-h-24 w-full rounded border px-3 py-2 text-sm" />
      {clientError ? <p className="text-xs text-red-600">{clientError}</p> : null}
      <button type="submit" disabled={isSubmitting} className="rounded bg-violet-600 px-4 py-2 text-sm text-white disabled:opacity-50">
        {isSubmitting ? t('common.pleaseWait') : submitLabel}
      </button>
    </form>
  )
}
