import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useFloorQuery } from '@/features/site/hooks/useFloorQuery'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { useDeleteUnitMutation } from '@/features/site/hooks/useUnitMutations'
import { useUnitQuery } from '@/features/site/hooks/useUnitQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function UnitDetailPage() {
  const { id: idRaw } = useParams<{ id: string }>()
  const id = parsePositiveInt(idRaw)
  const navigate = useNavigate()
  const toast = useToast()
  const [confirmOpen, setConfirmOpen] = useState(false)

  const canView = useEffectiveCan('unit.view')
  const canUpdate = useEffectiveCan('unit.update')
  const canDelete = useEffectiveCan('unit.delete')
  const canListOccupancies = useEffectiveCan('unit_occupancy.list')

  const { data, isPending, isError, error } = useUnitQuery(id ?? 0, {
    enabled: canView && id !== null,
  })

  const siteId = data?.site_id ?? 0
  const blockId = data?.block_id ?? 0
  const floorId = data?.floor_id ?? 0

  const { data: site } = useSiteQuery(siteId, { enabled: siteId > 0 })
  const { data: block } = useBlockQuery(blockId, { enabled: blockId > 0 })
  const { data: floor } = useFloorQuery(floorId, { enabled: floorId > 0 })

  const deleteMutation = useDeleteUnitMutation()

  if (!canView) {
    return <PermissionDeniedNotice permission="unit.view" />
  }

  if (id === null) {
    return <p className="text-sm">Invalid unit id.</p>
  }

  if (isPending) {
    return <p className="text-sm">Loading…</p>
  }

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
        {error instanceof Error ? error.message : 'Not found'}
      </div>
    )
  }

  const unit = data

  const handleDelete = () => {
    deleteMutation.mutate(unit.id, {
      onSuccess: () => {
        toast.success('Unit deleted.')
        navigate(`/floors/${unit.floor_id}/units`)
      },
      onError: (err) => toast.error(getErrorMessage(err, 'Could not delete unit.')),
    })
  }

  const deleteMsg = deleteMutation.isError ? getErrorMessage(deleteMutation.error) : null

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">Sites</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${unit.site_id}`}>{site?.code ?? unit.site_id}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${unit.site_id}/blocks`}>Blocks</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${unit.block_id}`}>{block?.code ?? unit.block_id}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${unit.block_id}/floors`}>Floors</Link>
        <span className="mx-1">/</span>
        <Link to={`/floors/${unit.floor_id}`}>Floor {floor?.number ?? ''}</Link>
        <span className="mx-1">/</span>
        <Link to={`/floors/${unit.floor_id}/units`}>Units</Link>
        <span className="mx-1">/</span>
        <span>{unit.unit_no}</span>
      </nav>

      <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
        <div>
          <h1 className="font-mono text-2xl font-semibold">{unit.unit_no}</h1>
          <p className="text-sm text-zinc-500">{unit.status}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {canListOccupancies ? (
            <Link
              to={`/units/${unit.id}/occupancies`}
              className="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600"
            >
              Occupancies
            </Link>
          ) : null}
          {canUpdate ? (
            <Link
              to={`/units/${unit.id}/edit`}
              className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white"
            >
              Edit
            </Link>
          ) : null}
          {canDelete ? (
            <button
              type="button"
              onClick={() => setConfirmOpen(true)}
              disabled={deleteMutation.isPending}
              className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-50"
            >
              {deleteMutation.isPending ? 'Deleting…' : 'Delete'}
            </button>
          ) : null}
        </div>
      </div>

      {deleteMsg ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          {deleteMsg}
        </div>
      ) : null}

      <dl className="grid max-w-xl gap-4 rounded-xl border p-6 dark:border-zinc-800">
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Type</dt>
          <dd className="mt-1 text-sm">{unit.type?.trim() ? unit.type : '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Gross / net / land</dt>
          <dd className="mt-1 text-sm">
            {unit.gross_area ?? '—'} / {unit.net_area ?? '—'} /{' '}
            {unit.land_share != null && unit.land_share !== '' ? String(unit.land_share) : '—'}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Occupant</dt>
          <dd className="mt-1 text-sm">
            {unit.occupant_name?.trim() ? unit.occupant_name : '—'}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Updated</dt>
          <dd className="mt-1 text-sm">{formatDateTime(unit.updated_at ?? unit.created_at)}</dd>
        </div>
      </dl>
      <ConfirmDialog
        isOpen={confirmOpen}
        title="Delete unit"
        description={`Delete unit "${unit.unit_no}"? This action cannot be undone.`}
        confirmText="Delete"
        cancelText="Cancel"
        variant="danger"
        isLoading={deleteMutation.isPending}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleDelete}
      />
    </div>
  )
}
