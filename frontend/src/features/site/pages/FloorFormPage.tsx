import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { FloorForm } from '@/features/site/components/FloorForm'
import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useFloorQuery } from '@/features/site/hooks/useFloorQuery'
import {
  useCreateFloorMutation,
  useUpdateFloorMutation,
} from '@/features/site/hooks/useFloorMutations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function FloorFormPage({ mode }: { mode: 'create' | 'edit' }) {
  const { t } = useTranslation(['site', 'common'])
  const { blockId: blockIdRaw, id: floorIdRaw } = useParams<{
    blockId?: string
    id?: string
  }>()
  const navigate = useNavigate()
  const blockId = parsePositiveInt(blockIdRaw)
  const floorId = parsePositiveInt(floorIdRaw)

  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})
  const toast = useToast()

  const canCreate = useEffectiveCan('floor.create')
  const canUpdate = useEffectiveCan('floor.update')
  const allowed = mode === 'create' ? canCreate : canUpdate

  const { data: block, isPending: loadingBlock } = useBlockQuery(blockId ?? 0, {
    enabled: mode === 'create' && blockId !== null && allowed,
  })

  const { data: existing, isPending: loadingFloor } = useFloorQuery(floorId ?? 0, {
    enabled: mode === 'edit' && floorId !== null && allowed,
  })

  const createMut = useCreateFloorMutation()
  const updateMut = useUpdateFloorMutation()

  if (!allowed) {
    return (
      <PermissionDeniedNotice permission={mode === 'create' ? 'floor.create' : 'floor.update'} />
    )
  }

  if (mode === 'create' && blockId === null) {
    return (
      <p className="text-sm">
        {t('site.common.invalidId')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  if (mode === 'create' && loadingBlock) {
    return <p className="text-sm">{t('site.common.loading')}</p>
  }

  if (mode === 'create' && !block) {
    return (
      <p className="text-sm">
        {t('site.common.notFound')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  if (mode === 'edit' && floorId === null) {
    return (
      <p className="text-sm">
        {t('site.common.invalidId')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  if (mode === 'edit' && loadingFloor) {
    return <p className="text-sm">{t('site.common.loading')}</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm">
        {t('site.common.notFound')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  const effectiveBlockId = mode === 'create' ? blockId! : existing!.block_id
  const effectiveSiteId = mode === 'create' ? block!.site_id : existing!.site_id

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('site.common.sites')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${effectiveSiteId}`}>{t('site.common.sites')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${effectiveBlockId}/floors`}>{t('site.common.floors')}</Link>
        <span className="mx-1">/</span>
        <span>{mode === 'create' ? t('site.common.new') : t('site.common.edit')}</span>
      </nav>
      <h1 className="text-2xl font-semibold">{mode === 'create' ? `${t('site.common.new')} ${t('site.common.floors')}` : `${t('site.common.edit')} ${t('site.common.floors')}`}</h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          {getErrorMessage(mutationError, t('site.common.requestFailed'))}
        </div>
      ) : null}

      <FloorForm
        siteId={effectiveSiteId}
        blockId={effectiveBlockId}
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? t('common.create') : t('common.save')}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          if (mode === 'create') {
            createMut.mutate(
              {
                site_id: values.site_id,
                block_id: values.block_id,
                number: values.number,
                label: values.label || undefined,
                sort_order: values.sort_order ?? undefined,
                status: values.status,
              },
              {
                onSuccess: (f) => {
                  toast.success(`${t('site.common.floors')} ${t('common.create')}`)
                  navigate(`/floors/${f.id}`)
                },
                onError: (e) => {
                  setServerErrors(extractValidationErrors(e))
                  toast.error(getErrorMessage(e, t('common.errorGeneric')))
                },
              },
            )
          } else if (floorId !== null && existing) {
            updateMut.mutate(
              {
                id: floorId,
                body: {
                  site_id: values.site_id,
                  block_id: values.block_id,
                  number: values.number,
                  label: values.label || undefined,
                  sort_order: values.sort_order ?? undefined,
                  status: values.status,
                },
              },
              {
                onSuccess: (f) => {
                  toast.success(`${t('site.common.floors')} ${t('common.update')}`)
                  navigate(`/floors/${f.id}`)
                },
                onError: (e) => {
                  setServerErrors(extractValidationErrors(e))
                  toast.error(getErrorMessage(e, t('common.errorGeneric')))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
