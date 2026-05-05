import { useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'

import {
  createResidentVehicle,
  deleteResidentVehicle,
  listResidentVehicles,
  updateResidentVehicle,
} from '@/features/resident/api/vehicleApi'
import type { ResidentVehicle, VehiclePayload } from '@/features/resident/types'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

function emptyPayload(residentId: number): VehiclePayload {
  return {
    resident_profile_id: residentId,
    plate_number: '',
    brand: '',
    model: '',
    color: '',
    status: 'active',
  }
}

export function ResidentVehiclesPage() {
  const { residentId: raw } = useParams<{ residentId: string }>()
  const residentId = parsePositiveInt(raw)
  const toast = useToast()
  const qc = useQueryClient()

  const canList = useEffectiveCan('resident_vehicle.list')
  const canCreate = useEffectiveCan('resident_vehicle.create')
  const canUpdate = useEffectiveCan('resident_vehicle.update')
  const canDelete = useEffectiveCan('resident_vehicle.delete')

  const [form, setForm] = useState<VehiclePayload>(() => emptyPayload(residentId ?? 0))
  const [editId, setEditId] = useState<number | null>(null)
  const [confirmDeleteId, setConfirmDeleteId] = useState<number | null>(null)
  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})

  const params = useMemo(
    () => ({ page: 1, per_page: 100, resident_profile_id: residentId ?? 0 }),
    [residentId],
  )
  const vehiclesQ = useQuery({
    queryKey: ['resident-vehicles', params],
    queryFn: () => listResidentVehicles(params),
    enabled: canList && residentId !== null,
  })

  const createMut = useMutation({
    mutationFn: createResidentVehicle,
    onSuccess: () => {
      toast.success('Vehicle created.')
      setForm(emptyPayload(residentId ?? 0))
      setServerErrors({})
      void qc.invalidateQueries({ queryKey: ['resident-vehicles'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, 'Could not create vehicle.'))
    },
  })

  const updateMut = useMutation({
    mutationFn: ({ id, body }: { id: number; body: Partial<VehiclePayload> }) =>
      updateResidentVehicle(id, body),
    onSuccess: () => {
      toast.success('Vehicle updated.')
      setEditId(null)
      setForm(emptyPayload(residentId ?? 0))
      setServerErrors({})
      void qc.invalidateQueries({ queryKey: ['resident-vehicles'] })
    },
    onError: (err) => {
      setServerErrors(extractValidationErrors(err))
      toast.error(getErrorMessage(err, 'Could not update vehicle.'))
    },
  })

  const deleteMut = useMutation({
    mutationFn: deleteResidentVehicle,
    onSuccess: () => {
      toast.success('Vehicle deleted.')
      setConfirmDeleteId(null)
      void qc.invalidateQueries({ queryKey: ['resident-vehicles'] })
    },
    onError: (err) => toast.error(getErrorMessage(err, 'Could not delete vehicle.')),
  })

  if (!canList) return <PermissionDeniedNotice permission="resident_vehicle.list" />
  if (residentId === null) return <p className="text-sm text-zinc-600">Invalid resident id.</p>

  const rows = vehiclesQ.data?.items ?? []
  const isSubmitting = createMut.isPending || updateMut.isPending

  const loadForEdit = (row: ResidentVehicle) => {
    setEditId(row.id)
    setServerErrors({})
    setForm({
      resident_profile_id: row.resident_profile_id,
      unit_id: row.unit_id ?? null,
      plate_number: row.plate_number,
      brand: row.brand ?? '',
      model: row.model ?? '',
      color: row.color ?? '',
      status: row.status,
    })
  }

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to={`/residents/${residentId}`} className="hover:text-violet-600">
          Resident {residentId}
        </Link>
        <span className="mx-1">/</span>
        <span>Vehicles</span>
      </nav>
      <h1 className="text-2xl font-semibold text-zinc-900 dark:text-zinc-50">Resident vehicles</h1>

      {(canCreate || (canUpdate && editId !== null)) && (
        <form
          className="grid gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault()
            const payload: VehiclePayload = {
              ...form,
              resident_profile_id: residentId,
              plate_number: form.plate_number.trim(),
              brand: form.brand?.trim() ? form.brand : null,
              model: form.model?.trim() ? form.model : null,
              color: form.color?.trim() ? form.color : null,
            }
            if (editId === null) createMut.mutate(payload)
            else updateMut.mutate({ id: editId, body: payload })
          }}
        >
          <div>
            <label className="block text-sm font-medium">Plate</label>
            <input
              value={form.plate_number}
              onChange={(e) => setForm((p) => ({ ...p, plate_number: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
            {serverErrors.plate_number ? (
              <p className="mt-1 text-xs text-red-600">{serverErrors.plate_number}</p>
            ) : null}
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
            <label className="block text-sm font-medium">Brand</label>
            <input
              value={form.brand ?? ''}
              onChange={(e) => setForm((p) => ({ ...p, brand: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Model</label>
            <input
              value={form.model ?? ''}
              onChange={(e) => setForm((p) => ({ ...p, model: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Color</label>
            <input
              value={form.color ?? ''}
              onChange={(e) => setForm((p) => ({ ...p, color: e.target.value }))}
              className="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-950"
            />
          </div>
          <div className="sm:col-span-2 flex gap-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              {isSubmitting ? 'Saving…' : editId === null ? 'Create vehicle' : 'Save vehicle'}
            </button>
            {editId !== null ? (
              <button
                type="button"
                className="rounded-lg border px-4 py-2 text-sm dark:border-zinc-700"
                onClick={() => {
                  setEditId(null)
                  setServerErrors({})
                  setForm(emptyPayload(residentId))
                }}
              >
                Cancel edit
              </button>
            ) : null}
          </div>
        </form>
      )}

      {!vehiclesQ.isPending && !vehiclesQ.isError && rows.length === 0 ? (
        <EmptyState title="No vehicles yet" description="Add resident vehicle records." />
      ) : null}

      {rows.length > 0 ? (
        <div className="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
              <thead className="bg-zinc-50 dark:bg-zinc-800/80">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Plate</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Brand / model</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Color</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                  <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
                {rows.map((row) => (
                  <tr key={row.id}>
                    <td className="px-4 py-3 text-sm font-mono">{row.plate_number}</td>
                    <td className="px-4 py-3 text-sm">
                      {row.brand?.trim() ? row.brand : '—'} / {row.model?.trim() ? row.model : '—'}
                    </td>
                    <td className="px-4 py-3 text-sm">{row.color?.trim() ? row.color : '—'}</td>
                    <td className="px-4 py-3 text-sm">{row.status}</td>
                    <td className="px-4 py-3 text-right text-sm">
                      <div className="flex justify-end gap-3">
                        {canUpdate ? (
                          <button type="button" className="text-violet-600 hover:underline" onClick={() => loadForEdit(row)}>
                            Edit
                          </button>
                        ) : null}
                        {canDelete ? (
                          <button type="button" className="text-red-600 hover:underline" onClick={() => setConfirmDeleteId(row.id)}>
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
        title="Delete vehicle"
        description="Delete this vehicle?"
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
