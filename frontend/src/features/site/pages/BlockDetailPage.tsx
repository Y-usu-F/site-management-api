import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { useDeleteBlockMutation } from '@/features/site/hooks/useBlockMutations'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function BlockDetailPage() {
  const { t } = useTranslation(['site', 'common'])
  const { id: idRaw } = useParams<{ id: string }>()
  const id = parsePositiveInt(idRaw)
  const navigate = useNavigate()
  const toast = useToast()
  const [confirmOpen, setConfirmOpen] = useState(false)

  const canView = useEffectiveCan('block.view')
  const canUpdate = useEffectiveCan('block.update')
  const canDelete = useEffectiveCan('block.delete')
  const canListFloors = useEffectiveCan('floor.list')

  const { data, isPending, isError, error } = useBlockQuery(id ?? 0, {
    enabled: canView && id !== null,
  })

  const siteId = data?.site_id ?? 0
  const { data: site } = useSiteQuery(siteId, {
    enabled: siteId > 0,
  })

  const deleteMutation = useDeleteBlockMutation()

  if (!canView) {
    return <PermissionDeniedNotice permission="block.view" />
  }

  if (id === null) {
    return <p className="text-sm text-zinc-600">{t('site.common.invalidId')}</p>
  }

  if (isPending) {
    return <p className="text-sm text-zinc-600">{t('site.common.loading')}</p>
  }

  if (isError || !data) {
    return (
      <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
        {error instanceof Error ? error.message : t('site.common.notFound')}
      </div>
    )
  }

  const block = data

  const handleDelete = () => {
    deleteMutation.mutate(block.id, {
      onSuccess: () => {
        toast.success(`${t('site.common.blocks')} ${t('common.delete')}`)
        navigate(`/sites/${block.site_id}/blocks`)
      },
      onError: (err) => toast.error(getErrorMessage(err, t('common.errorGeneric'))),
    })
  }

  const deleteMsg = deleteMutation.isError ? getErrorMessage(deleteMutation.error) : null

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites" className="hover:text-violet-600">
          {t('site.common.sites')}
        </Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${block.site_id}`} className="hover:text-violet-600">
          {site?.code ?? `Site ${block.site_id}`}
        </Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${block.site_id}/blocks`} className="hover:text-violet-600">
          {t('site.common.blocks')}
        </Link>
        <span className="mx-1">/</span>
        <span>{block.code}</span>
      </nav>

      <div className="flex flex-col gap-4 sm:flex-row sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{block.name}</h1>
          <p className="font-mono text-sm text-zinc-500">{block.code}</p>
        </div>
        <div className="flex flex-wrap gap-2">
          {canListFloors ? (
            <Link
              to={`/blocks/${block.id}/floors`}
              className="rounded-lg border px-3 py-2 text-sm dark:border-zinc-600"
            >
              {t('site.common.floors')}
            </Link>
          ) : null}
          {canUpdate ? (
            <Link
              to={`/blocks/${block.id}/edit`}
              className="rounded-lg bg-violet-600 px-3 py-2 text-sm text-white"
            >
              {t('site.common.edit')}
            </Link>
          ) : null}
          {canDelete ? (
            <button
              type="button"
              onClick={() => setConfirmOpen(true)}
              disabled={deleteMutation.isPending}
              className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 disabled:opacity-50"
            >
              {deleteMutation.isPending ? t('site.common.deleting') : t('site.common.delete')}
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
          <dt className="text-xs font-semibold uppercase text-zinc-500">{t('site.common.status')}</dt>
          <dd className="mt-1 text-sm">{block.status}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">{t('site.form.sortOrder')}</dt>
          <dd className="mt-1 text-sm">{block.sort_order ?? '—'}</dd>
        </div>
        <div>
          <dt className="text-xs font-semibold uppercase text-zinc-500">{t('site.common.updated')}</dt>
          <dd className="mt-1 text-sm">{formatDateTime(block.updated_at ?? block.created_at)}</dd>
        </div>
      </dl>
      <ConfirmDialog
        isOpen={confirmOpen}
        title={t('site.common.delete')}
        description={t('common.confirm')}
        confirmText={t('site.common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        isLoading={deleteMutation.isPending}
        onClose={() => setConfirmOpen(false)}
        onConfirm={handleDelete}
      />
    </div>
  )
}
