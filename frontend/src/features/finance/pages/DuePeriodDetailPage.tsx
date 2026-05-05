import { Link, useNavigate, useParams } from 'react-router-dom'

import { useDeleteDuePeriodMutation } from '@/features/finance/hooks/useDuePeriodMutations'
import { useDuePeriodQuery } from '@/features/finance/hooks/useDuePeriodQuery'
import { FinanceStatusBadge } from '@/features/finance/components/FinanceStatusBadge'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useState } from 'react'

export function DuePeriodDetailPage() {
  const { id } = useParams()
  const parsedId = parsePositiveInt(id)
  const canView = useEffectiveCan('due_period.view')
  const canDelete = useEffectiveCan('due_period.delete')
  const query = useDuePeriodQuery(parsedId ?? 0, canView && parsedId !== null)
  const del = useDeleteDuePeriodMutation()
  const [open, setOpen] = useState(false)
  const navigate = useNavigate()
  const toast = useToast()

  if (!canView) return <PermissionDeniedNotice permission="due_period.view" />
  if (parsedId === null) return <div>Gecersiz ID.</div>
  if (!query.data) return <div>Yukleniyor...</div>

  const row = query.data
  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{row.period_key}</h1>
        <div className="flex gap-3 text-sm">
          <Link to="/finance/due-periods">Back</Link>
          <Link className="text-violet-600" to={`/finance/due-periods/${row.id}/edit`}>
            Edit
          </Link>
          {canDelete ? (
            <button type="button" className="text-red-600" onClick={() => setOpen(true)}>
              Delete
            </button>
          ) : null}
        </div>
      </div>
      <div className="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
        <div>ID: {row.id}</div>
        <div>Site id: {row.site_id}</div>
        <div>Start: {row.start_date}</div>
        <div>End: {row.end_date}</div>
        <div>Due: {row.due_date}</div>
        <div>Status: <FinanceStatusBadge status={row.status} /></div>
      </div>
      <ConfirmDialog
        isOpen={open}
        title="Due period silinsin mi?"
        description="Bu islem geri alinamaz."
        confirmText={del.isPending ? 'Deleting…' : 'Delete'}
        cancelText="Vazgec"
        variant="danger"
        onClose={() => setOpen(false)}
        onConfirm={() => {
          del.mutate(row.id, {
            onSuccess: () => {
              toast.success('Due period silindi.')
              navigate('/finance/due-periods')
            },
            onError: (err) => toast.error(getErrorMessage(err)),
          })
        }}
      />
    </div>
  )
}
