import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useDeleteFloorMutation } from '@/features/site/hooks/useFloorMutations'
import { useFloorQuery } from '@/features/site/hooks/useFloorQuery'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function FloorDetailPage() {
  const { t } = useTranslation(['site', 'common'])
  const { id: idRaw } = useParams<{ id: string }>()
  const id = parsePositiveInt(idRaw)
  const navigate = useNavigate()
  const toast = useToast()
  const [confirmOpen, setConfirmOpen] = useState(false)

  const canView = useEffectiveCan('floor.view')
  const canUpdate = useEffectiveCan('floor.update')
  const canDelete = useEffectiveCan('floor.delete')
  const canListUnits = useEffectiveCan('unit.list')

  const { data, isPending, isError, error } = useFloorQuery(id ?? 0, {
    enabled: canView && id !== null,
  })

  const siteId = data?.site_id ?? 0
  const blockId = data?.block_id ?? 0

  const { data: site } = useSiteQuery(siteId, { enabled: siteId > 0 })
  const { data: block } = useBlockQuery(blockId, { enabled: blockId > 0 })

  const deleteMutation = useDeleteFloorMutation()

  if (!canView) {
    return <PermissionDeniedNotice permission="floor.view" />
  }

  if (id === null) {
    return <p className="text-sm">{t('invalidId', { ns: 'site' })}</p>
  }

  if (isPending) {
    return <p className="text-sm">{t('loading', { ns: 'site' })}</p>
  }

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
        {error instanceof Error ? error.message : t('notFound', { ns: 'site' })}
      </div>
    )
  }

  const floor = data

  const handleDelete = () => {
    deleteMutation.mutate(floor.id, {
      onSuccess: () => {
        toast.success(`${t('floors', { ns: 'site' })} ${t('delete', { ns: 'common' })}`)
        navigate(`/blocks/${floor.block_id}/floors`)
      },
      onError: (err) => toast.error(getErrorMessage(err, t('errorGeneric', { ns: 'common' }))),
    })
  }

  const deleteMsg = deleteMutation.isError ? getErrorMessage(deleteMutation.error) : null

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${floor.site_id}`}>{site?.code ?? floor.site_id}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${floor.site_id}/blocks`}>{t('blocks', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${floor.block_id}`}>{block?.code ?? floor.block_id}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${floor.block_id}/floors`}>{t('floors', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <span>{floor.number}</span>
      </nav>

      <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">
            {t('floors', { ns: 'site' })} {floor.number}
            {floor.label?.trim() ? ` · ${floor.label}` : ''}
          </h1>
          <p className="text-sm text-zinc-500">{floor.status}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {canListUnits ? (
            <Link
              to={`/floors/${floor.id}/units`}
              className="rounded-lg border px-3 py-2 text-sm dark:border-zinc-600"
            >
              {t('units', { ns: 'site' })}
            </Link>
          ) : null}
          {canUpdate ? (
            <Link
              to={`/floors/${floor.id}/edit`}
              className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white"
            >
              {t('edit', { ns: 'site' })}
            </Link>
          ) : null}
          {canDelete ? (
            <button
              type="button"
              onClick={() => setConfirmOpen(true)}
              disabled={deleteMutation.isPending}
              className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-50"
            >
              {deleteMutation.isPending ? t('deleting', { ns: 'site' }) : t('delete', { ns: 'site' })}
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
          <dt className="text-xs font-semibold uppercase text-zinc-500">{t('form.sortOrder', { ns: 'site' })}</dt>
          <dd className="mt-1 text-sm">{floor.sort_order ?? '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">{t('updated', { ns: 'site' })}</dt>
          <dd className="mt-1 text-sm">{formatDateTime(floor.updated_at ?? floor.created_at)}</dd>
        </div>
      </dl>
      <ConfirmDialog
        isOpen={confirmOpen}
        title={t('delete', { ns: 'site' })}
        description={t('confirm', { ns: 'common' })}
        confirmText={t('delete', { ns: 'site' })}
        cancelText={t('cancel', { ns: 'common' })}
        variant="danger"
        isLoading={deleteMutation.isPending}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleDelete}
      />
    </div>
  )
}
