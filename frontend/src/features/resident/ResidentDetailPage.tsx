import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'

import { listOccupancies } from '@/features/resident/api/occupancyApi'
import { listResidentContacts } from '@/features/resident/api/contactApi'
import { listResidentVehicles } from '@/features/resident/api/vehicleApi'
import { useDeleteResidentMutation } from '@/features/resident/hooks/useResidentMutations'
import { useResidentQuery } from '@/features/resident/hooks/useResidentQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'

export function ResidentDetailPage() {
  const { id: idParam } = useParams<{ id: string }>()
  const id = parsePositiveInt(idParam)
  const navigate = useNavigate()
  const toast = useToast()
  const [confirmOpen, setConfirmOpen] = useState(false)

  const canView = useEffectiveCan('resident.view')
  const canUpdate = useEffectiveCan('resident.update')
  const canDelete = useEffectiveCan('resident.delete')
  const canContacts = useEffectiveCan('resident_contact.list')
  const canVehicles = useEffectiveCan('resident_vehicle.list')
  const canOccupancies = useEffectiveCan('unit_occupancy.list')

  const { data, isPending, isError, error } = useResidentQuery(id ?? 0, canView && id !== null)
  const deleteMutation = useDeleteResidentMutation()

  const contactsQ = useQuery({
    queryKey: ['resident-contacts', 'resident-preview', id],
    queryFn: () => listResidentContacts({ resident_profile_id: id ?? 0, page: 1, per_page: 5 }),
    enabled: canContacts && id !== null,
  })

  const vehiclesQ = useQuery({
    queryKey: ['resident-vehicles', 'resident-preview', id],
    queryFn: () => listResidentVehicles({ resident_profile_id: id ?? 0, page: 1, per_page: 5 }),
    enabled: canVehicles && id !== null,
  })

  const occupanciesQ = useQuery({
    queryKey: ['unit-occupancies', 'resident-preview', id],
    queryFn: () => listOccupancies({ resident_profile_id: id ?? 0, page: 1, per_page: 5 }),
    enabled: canOccupancies && id !== null,
  })

  if (!canView) {
    return <PermissionDeniedNotice permission="resident.view" />
  }
  if (id === null) return <p className="text-sm text-zinc-600">Invalid resident id.</p>
  if (isPending) return <p className="text-sm text-zinc-600">Loading resident…</p>

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-red-950/40">
        <p className="text-sm text-red-800 dark:text-red-200">
          {error instanceof Error ? error.message : 'Resident not found'}
        </p>
      </div>
    )
  }

  const resident = data

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <nav className="text-xs text-zinc-500">
            <Link to="/residents" className="hover:text-violet-600">
              Residents
            </Link>
            <span className="mx-1">/</span>
            <span className="text-zinc-700 dark:text-zinc-300">
              {resident.first_name} {resident.last_name}
            </span>
          </nav>
          <h1 className="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
            {resident.first_name} {resident.last_name}
          </h1>
          <p className="mt-1 text-sm text-zinc-500">
            {resident.identity_number?.trim() ? resident.identity_number : 'No identity number'}
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          {canContacts ? (
            <Link to={`/residents/${resident.id}/contacts`} className="rounded-lg border px-3 py-2 text-sm dark:border-zinc-700">
              Contacts
            </Link>
          ) : null}
          {canVehicles ? (
            <Link to={`/residents/${resident.id}/vehicles`} className="rounded-lg border px-3 py-2 text-sm dark:border-zinc-700">
              Vehicles
            </Link>
          ) : null}
          {canUpdate ? (
            <Link to={`/residents/${resident.id}/edit`} className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white">
              Edit
            </Link>
          ) : null}
          {canDelete ? (
            <button
              type="button"
              onClick={() => setConfirmOpen(true)}
              disabled={deleteMutation.isPending}
              className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-50 dark:border-red-800 dark:text-red-300"
            >
              {deleteMutation.isPending ? 'Deleting…' : 'Delete'}
            </button>
          ) : null}
        </div>
      </div>

      <dl className="grid max-w-3xl gap-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2">
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Phone</dt>
          <dd className="mt-1 text-sm">{resident.phone?.trim() ? resident.phone : '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Email</dt>
          <dd className="mt-1 text-sm">{resident.email?.trim() ? resident.email : '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Status</dt>
          <dd className="mt-1 text-sm">{resident.status}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Updated</dt>
          <dd className="mt-1 text-sm">{formatDateTime(resident.updated_at ?? resident.created_at)}</dd>
        </div>
      </dl>

      {canContacts ? (
        <section className="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">Contacts</h2>
            <Link to={`/residents/${resident.id}/contacts`} className="text-sm text-violet-600 hover:underline">
              Manage
            </Link>
          </div>
          {contactsQ.data?.items.length ? (
            <ul className="space-y-1 text-sm">
              {contactsQ.data.items.map((c) => (
                <li key={c.id}>
                  {c.type}: {c.value}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-zinc-500">No contacts.</p>
          )}
        </section>
      ) : null}

      {canVehicles ? (
        <section className="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-sm font-semibold uppercase tracking-wide text-zinc-500">Vehicles</h2>
            <Link to={`/residents/${resident.id}/vehicles`} className="text-sm text-violet-600 hover:underline">
              Manage
            </Link>
          </div>
          {vehiclesQ.data?.items.length ? (
            <ul className="space-y-1 text-sm">
              {vehiclesQ.data.items.map((v) => (
                <li key={v.id}>
                  {v.plate_number} {v.brand ? `· ${v.brand}` : ''}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-zinc-500">No vehicles.</p>
          )}
        </section>
      ) : null}

      {canOccupancies ? (
        <section className="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
          <h2 className="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">Occupancies</h2>
          {occupanciesQ.data?.items.length ? (
            <ul className="space-y-1 text-sm">
              {occupanciesQ.data.items.map((o) => (
                <li key={o.id}>
                  Unit #{o.unit_id} · {o.relationship_type} · {o.status}
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-zinc-500">No occupancies found for this resident.</p>
          )}
        </section>
      ) : null}

      <ConfirmDialog
        isOpen={confirmOpen}
        title="Delete resident"
        description={`Delete "${resident.first_name} ${resident.last_name}"?`}
        confirmText="Delete"
        cancelText="Cancel"
        variant="danger"
        isLoading={deleteMutation.isPending}
        onClose={() => setConfirmOpen(false)}
        onConfirm={() => {
          deleteMutation.mutate(resident.id, {
            onSuccess: () => {
              toast.success('Resident deleted.')
              navigate('/residents')
            },
            onError: (err) => toast.error(getErrorMessage(err, 'Could not delete resident.')),
          })
        }}
      />
    </div>
  )
}
