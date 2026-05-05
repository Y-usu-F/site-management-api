import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'

import {
  createOccupancy,
  deleteOccupancy,
  listOccupancies,
  updateOccupancy,
} from '@/features/resident/api/occupancyApi'
import { listResidents } from '@/features/resident/api/residentApi'
import type { OccupancyPayload, UnitOccupancy } from '@/features/resident/types'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

function defaultPayload(unitId: number): OccupancyPayload {
  return {
    unit_id: unitId,
    resident_profile_id: 0,
    relationship_type: 'tenant',
    start_date: '',
    end_date: '',
    is_primary: false,
    status: 'active',
  }
}

export function UnitOccupanciesPage() {
  const { unitId: raw } = useParams<{ unitId: string }>()
  const unitId = parsePositiveInt(raw)
  const toast = useToast()
  const qc = useQueryClient()

  const canList = useEffectiveCan('unit_occupancy.list')
  const canCreate = useEffectiveCan('unit_occupancy.create')
  const canUpdate = useEffectiveCan('unit_occupancy.update')
  const canDelete = useEffectiveCan('unit_occupancy.delete')

  const [form, setForm] = useState<OccupancyPayload>(() => defaultPayload(unitId ?? 0))
  const [editId, setEditId] = useState<number | null>(null)
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null)
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})

  const occupancyParams = useMemo(
    () => ({ page: 1, per_page: 100, unit_id: unitId ?? 0 }),
    [unitId],
  )

  const occupanciesQ = useQuery({
    queryKey: ['unit-occupancies', occupancyParams],
    queryFn: () => listOccupancies(occupancyParams),
    enabled: canList && unitId !== null,
  })

  const residentsQ = useQuery({
    queryKey: ['residents', 'occupancy-select'],
    queryFn: () => listResidents({ page: 1, per_page: 200 }),
    enabled: canCreate || canUpdate,
  })

  const createMut = useMutation({
    mutationFn: createOccupancy,
    onSuccess: () => {
      toast.success('Occupancy created.')
      setServerErrors({})
      setForm(defaultPayload(unitId ?? 0))
      void qc.invalidateQueries({ queryKey: ['unit-occupancies'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, 'Could not create occupancy.'))
    },
  })

  const updateMut = useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<OccupancyPayload> }) =>
      updateOccupancy(id, body),
    onSuccess: () => {
      toast.success('Occupancy updated.')
      setServerErrors({})
      setEditId(null)
      setForm(defaultPayload(unitId ?? 0))
      void qc.invalidateQueries({ queryKey: ['unit-occupancies'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, 'Could not update occupancy.'))
    },
  })

  const deleteMut = useMutation({
    mutationFn: deleteOccupancy,
    onSuccess: () => {
      toast.success('Occupancy deleted.')
      setConfirmDeleteId(null)
      void qc.invalidateQueries({ queryKey: ['unit-occupancies'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete occupancy.')),
  })

  if (!canList) return <PermissionDeniedNotice permission="unit_occupancy.list" />
  if (unitId === null) return <p className="text-sm text-zinc-600">Invalid unit id.</p>

  const rows = occupanciesQ.data?.items ?? []
  const residentOptions = residentsQ.data?.items ?? []
  const isSubmitting = createMut.isPending || updateMut.isPending

  const loadForEdit = (row: UnitOccupancy) => {
    setEditId(row.id)
    setServerErrors({})
    setForm({
      unit_id: row.unit_id,
      resident_profile_id: row.resident_profile_id,
      relationship_type: row.relationship_type,
      start_date: row.start_date,
      end_date: row.end_date ?? '',
      is_primary: Boolean(Number(row.is_primary)),
      status: row.status,
    })
  }

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to={`/units/${unitId}`} className="hover:text-violet-600">
          Unit {unitId}
        </Link>
        <span className="mx-1">/</span>
        <span>Occupancies</span>
      </nav>

      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">Unit occupancies</h1>

      {(canCreate || (canUpdate && editId !== null)) && (
        <form
          className="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault()
            const payload: OccupancyPayload = {
              ...form,
              unit_id: unitId,
              end_date: form.end_date?.trim() ? form.end_date : null,
            }
            if (editId === null) {
              createMut.mutate(payload)
            } else {
              updateMut.mutate({ id: editId, body: payload })
            }
          }}
        >
          <div className="sm:col-span-2">
            <label className="block text-sm font-medium">Resident</label>
            <select
              value={form.resident_profile_id}
              onChange={(e) => setForm((p) => ({ ...p, resident_profile_id: Number(e.target.value) }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            >
              <option value={0}>Select resident</option>
              {residentOptions.map((r) => (
                <option key={r.id} value={r.id}>
                  {r.first_name} {r.last_name}
                </option>
              ))}
            </select>
            {serverErrors.resident_profile_id ? (
              <p className="mt-1 text-xs text-red-600">{serverErrors.resident_profile_id}</p>
            ) : null}
          </div>
          <div>
            <label className="block text-sm font-medium">Relationship type</label>
            <select
              value={form.relationship_type}
              onChange={(e) => setForm((p) => ({ ...p, relationship_type: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            >
              <option value="owner">owner</option>
              <option value="tenant">tenant</option>
              <option value="resident">resident</option>
              <option value="family_member">family_member</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium">Status</label>
            <select
              value={form.status}
              onChange={(e) => setForm((p) => ({ ...p, status: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            >
              <option value="active">active</option>
              <option value="passive">passive</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium">Start date</label>
            <input
              type="date"
              value={form.start_date}
              onChange={(e) => setForm((p) => ({ ...p, start_date: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            {serverErrors.start_date ? <p className="mt-1 text-xs text-red-600">{serverErrors.start_date}</p> : null}
          </div>
          <div>
            <label className="block text-sm font-medium">End date</label>
            <input
              type="date"
              value={form.end_date ?? ''}
              onChange={(e) => setForm((p) => ({ ...p, end_date: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            {serverErrors.end_date ? <p className="mt-1 text-xs text-red-600">{serverErrors.end_date}</p> : null}
          </div>
          <label className="inline-flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={Boolean(form.is_primary)}
              onChange={(e) => setForm((p) => ({ ...p, is_primary: e.target.checked }))}
            />
            is_primary
          </label>
          <div className="sm:col-span-2 flex gap-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              {isSubmitting ? 'Saving…' : editId === null ? 'Create occupancy' : 'Save occupancy'}
            </button>
            {editId !== null ? (
              <button
                type="button"
                className="rounded-lg border px-4 py-2 text-sm dark:border-zinc-700"
                onClick={() => {
                  setEditId(null)
                  setServerErrors({})
                  setForm(defaultPayload(unitId))
                }}
              >
                Cancel edit
              </button>
            ) : null}
          </div>
        </form>
      )}

      {occupanciesQ.isPending ? <p className="text-sm text-zinc-500">Loading occupancies…</p> : null}
      {occupanciesQ.isError ? (
        <p className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {getErrorMessage(occupanciesQ.error, 'Could not load occupancies.')}
        </p>
      ) : null}

      {!occupanciesQ.isPending && !occupanciesQ.isError && rows.length === 0 ? (
        <EmptyState title="No occupancies yet" description="Create occupancy records for this unit." />
      ) : null}

      {rows.length > 0 ? (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Resident</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Type</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Date</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Primary</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3 text-sm">{row.resident_profile_id}</td>
                    <td className="px-4 py-3 text-sm">{row.relationship_type}</td>
                    <td className="px-4 py-3 text-sm">
                      {row.start_date} {row.end_date ? `- ${row.end_date}` : ''}
                    </td>
                    <td className="px-4 py-3 text-sm">{Number(row.is_primary) === 1 ? 'yes' : 'no'}</td>
                    <td className="px-4 py-3 text-sm">{row.status}</td>
                    <td className="px-4 py-3 text-right text-sm">
                      <div className="flex justify-end gap-3">
                        {canUpdate ? (
                          <button
                            type="button"
                            className="text-violet-600 hover:underline"
                            onClick={() => loadForEdit(row)}
                          >
                            Edit
                          </button>
                        ) : null}
                        {canDelete ? (
                          <button
                            type="button"
                            className="text-red-600 hover:underline"
                            onClick={() => setConfirmDeleteId(row.id)}
                          >
                            Delete
                          </button>
                        ) : null}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      ) : null}

      <ConfirmDialog
        isOpen={confirmDeleteId !== null}
        title="Delete occupancy"
        description="Delete this occupancy record?"
        confirmText="Delete"
        cancelText="Cancel"
        variant="danger"
        isLoading={deleteMut.isPending}
        onClose={() => setConfirmDeleteId(null)}
        onConfirm={() => {
          if (confirmDeleteId === null) return
          deleteMut.mutate(confirmDeleteId)
        }}
      />
    </div>
  )
}
