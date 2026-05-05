import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { useDeleteSiteMutation } from '@/features/site/hooks/useSiteMutations'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function SiteDetailPage() {
  const { id: idParam } = useParams<{ id: string }>()
  const id = parsePositiveInt(idParam)
  const navigate = useNavigate()
  const toast = useToast()
  const [confirmOpen, setConfirmOpen] = useState(false)

  const canView = useEffectiveCan('site.view')
  const canUpdate = useEffectiveCan('site.update')
  const canDelete = useEffectiveCan('site.delete')
  const canListBlocks = useEffectiveCan('block.list')

  const { data, isPending, isError, error } = useSiteQuery(id ?? 0, {
    enabled: canView && id !== null,
  })

  const deleteMutation = useDeleteSiteMutation()

  if (!canView) {
    return <PermissionDeniedNotice permission="site.view" />
  }

  if (id === null) {
    return (
      <div className="rounded-xl border border-zinc-200 p-6 dark:border-zinc-800">
        <p className="text-sm text-zinc-600">Invalid site id.</p>
        <Link to="/sites" className="mt-2 inline-block text-violet-600">
          Back to sites
        </Link>
      </div>
    )
  }

  if (isPending) {
    return <p className="text-sm text-zinc-600">Loading site…</p>
  }

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-900">
        <p className="text-sm text-red-800">
          {error instanceof Error ? error.message : 'Site not found'}
        </p>
        <Link to="/sites" className="mt-2 inline-block text-sm text-violet-600">
          Back to sites
        </Link>
      </div>
    )
  }

  const site = data

  const handleDelete = () => {
    deleteMutation.mutate(site.id, {
      onSuccess: () => {
        toast.success('Site deleted.')
        navigate('/sites')
      },
      onError: (err) => toast.error(getErrorMessage(err, 'Could not delete site.')),
    })
  }

  const deleteMessage = deleteMutation.isError ? getErrorMessage(deleteMutation.error) : null

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <nav className="text-xs text-zinc-500">
            <Link to="/sites" className="hover:text-violet-600">
              Sites
            </Link>
            <span className="mx-1">/</span>
            <span className="text-zinc-700 dark:text-zinc-300">{site.code}</span>
          </nav>
          <h1 className="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-50">
            {site.name}
          </h1>
          <p className="mt-1 font-mono text-sm text-zinc-500">{site.code}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {canListBlocks ? (
            <Link
              to={`/sites/${site.id}/blocks`}
              className="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600"
            >
              Blocks
            </Link>
          ) : null}
          {canUpdate ? (
            <Link
              to={`/sites/${site.id}/edit`}
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
              className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-50 dark:border-red-800 dark:text-red-300"
            >
              {deleteMutation.isPending ? 'Deleting…' : 'Delete'}
            </button>
          ) : null}
        </div>
      </div>

      {deleteMessage ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
          {deleteMessage}
        </div>
      ) : null}

      <dl className="grid max-w-xl gap-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Status</dt>
          <dd className="mt-1 text-sm">{site.status}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Address</dt>
          <dd className="mt-1 text-sm text-zinc-800 dark:text-zinc-200">
            {site.address?.trim() ? site.address : '—'}
          </dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">Updated</dt>
          <dd className="mt-1 text-sm">{formatDateTime(site.updated_at ?? site.created_at)}</dd>
        </div>
      </dl>
      <ConfirmDialog
        isOpen={confirmOpen}
        title="Delete site"
        description={`Delete "${site.name}"? This action cannot be undone.`}
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
