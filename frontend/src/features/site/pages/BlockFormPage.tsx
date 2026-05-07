import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { BlockForm } from '@/features/site/components/BlockForm'
import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import {
  useCreateBlockMutation,
  useUpdateBlockMutation,
} from '@/features/site/hooks/useBlockMutations'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function BlockFormPage({ mode }: { mode: 'create' | 'edit' }) {
  const { t } = useTranslation(['site', 'common'])
  const { siteId: siteIdRaw, id: blockIdRaw } = useParams<{
    siteId?: string
    id?: string
  }>()
  const navigate = useNavigate()
  const siteId = parsePositiveInt(siteIdRaw)
  const blockId = parsePositiveInt(blockIdRaw)

  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})
  const toast = useToast()

  const canCreate = useEffectiveCan('block.create')
  const canUpdate = useEffectiveCan('block.update')
  const allowed = mode === 'create' ? canCreate : canUpdate

  const { data: existing, isPending: loadingBlock } = useBlockQuery(blockId ?? 0, {
    enabled: mode === 'edit' && blockId !== null && allowed,
  })

  const createMut = useCreateBlockMutation()
  const updateMut = useUpdateBlockMutation()

  if (!allowed) {
    return (
      <PermissionDeniedNotice permission={mode === 'create' ? 'block.create' : 'block.update'} />
    )
  }

  if (mode === 'create' && siteId === null) {
    return (
      <p className="text-sm">
        {t('site.common.invalidId')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  if (mode === 'edit' && blockId === null) {
    return (
      <p className="text-sm">
        {t('site.common.invalidId')}. <Link to="/sites">{t('site.common.sites')}</Link>
      </p>
    )
  }

  if (mode === 'edit' && loadingBlock) {
    return <p className="text-sm text-zinc-600">{t('site.common.loading')}</p>
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
  const effectiveSiteId = mode === 'create' ? siteId! : existing!.site_id

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('site.common.sites')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${effectiveSiteId}`}>{t('site.common.sites')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${effectiveSiteId}/blocks`}>{t('site.common.blocks')}</Link>
        <span className="mx-1">/</span>
        <span>{mode === 'create' ? t('site.common.new') : t('site.common.edit')}</span>
      </nav>
      <h1 className="text-2xl font-semibold">{mode === 'create' ? `${t('site.common.new')} ${t('site.common.blocks')}` : `${t('site.common.edit')} ${t('site.common.blocks')}`}</h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          {getErrorMessage(mutationError, t('site.common.requestFailed'))}
        </div>
      ) : null}

      <BlockForm
        siteId={effectiveSiteId}
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
                name: values.name,
                code: values.code,
                sort_order: values.sort_order ?? undefined,
                status: values.status,
              },
              {
                onSuccess: (b) => {
                  toast.success(`${t('site.common.blocks')} ${t('common.create')}`)
                  navigate(`/blocks/${b.id}`)
                },
                onError: (e) => {
                  setServerErrors(extractValidationErrors(e))
                  toast.error(getErrorMessage(e, t('common.errorGeneric')))
                },
              },
            )
          } else if (blockId !== null && existing) {
            updateMut.mutate(
              {
                id: blockId,
                body: {
                  site_id: values.site_id,
                  name: values.name,
                  code: values.code,
                  sort_order: values.sort_order ?? undefined,
                  status: values.status,
                },
              },
              {
                onSuccess: (b) => {
                  toast.success(`${t('site.common.blocks')} ${t('common.update')}`)
                  navigate(`/blocks/${b.id}`)
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
