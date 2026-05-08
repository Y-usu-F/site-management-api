import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { UnitForm } from '@/features/site/components/UnitForm'
import { useFloorQuery } from '@/features/site/hooks/useFloorQuery'
import {
  useCreateUnitMutation,
  useUpdateUnitMutation,
} from '@/features/site/hooks/useUnitMutations'
import { useUnitQuery } from '@/features/site/hooks/useUnitQuery'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { extractValidationErrors, getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'

export function UnitFormPage({ mode }: { mode: 'create' | 'edit' }) {
  const { t } = useTranslation(['site', 'common'])
  const { floorId: floorIdRaw, id: unitIdRaw } = useParams<{
    floorId?: string
    id?: string
  }>()
  const navigate = useNavigate()
  const floorId = parsePositiveInt(floorIdRaw)
  const unitId = parsePositiveInt(unitIdRaw)

  const [serverErrors, setServerErrors] = useState<Record<string, string>>({})
  const toast = useToast()

  const canCreate = useEffectiveCan('unit.create')
  const canUpdate = useEffectiveCan('unit.update')
  const allowed = mode === 'create' ? canCreate : canUpdate

  const { data: floor, isPending: loadingFloor } = useFloorQuery(floorId ?? 0, {
    enabled: mode === 'create' && floorId !== null && allowed,
  })

  const { data: existing, isPending: loadingUnit } = useUnitQuery(unitId ?? 0, {
    enabled: mode === 'edit' && unitId !== null && allowed,
  })

  const createMut = useCreateUnitMutation()
  const updateMut = useUpdateUnitMutation()

  if (!allowed) {
    return (
      <PermissionDeniedNotice permission={mode === 'create' ? 'unit.create' : 'unit.update'} />
    )
  }

  if (mode === 'create' && floorId === null) {
    return (
      <p className="text-sm">
        {t('invalidId', { ns: 'site' })}. <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
      </p>
    )
  }

  if (mode === 'create' && loadingFloor) {
    return <p className="text-sm">{t('loading', { ns: 'site' })}</p>
  }

  if (mode === 'create' && !floor) {
    return (
      <p className="text-sm">
        {t('notFound', { ns: 'site' })}. <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
      </p>
    )
  }

  if (mode === 'edit' && unitId === null) {
    return (
      <p className="text-sm">
        {t('invalidId', { ns: 'site' })}. <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
      </p>
    )
  }

  if (mode === 'edit' && loadingUnit) {
    return <p className="text-sm">{t('loading', { ns: 'site' })}</p>
  }

  if (mode === 'edit' && !existing) {
    return (
      <p className="text-sm">
        {t('notFound', { ns: 'site' })}. <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
      </p>
    )
  }

  const isSubmitting = createMut.isPending || updateMut.isPending
  const mutationError = createMut.error ?? updateMut.error

  const effectiveFloorId = mode === 'create' ? floorId! : existing!.floor_id
  const effectiveBlockId = mode === 'create' ? floor!.block_id : existing!.block_id
  const effectiveSiteId = mode === 'create' ? floor!.site_id : existing!.site_id

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/floors/${effectiveFloorId}/units`}>{t('units', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <span>{mode === 'create' ? t('new', { ns: 'site' }) : t('edit', { ns: 'site' })}</span>
      </nav>
      <h1 className="text-2xl font-semibold">{mode === 'create' ? `${t('new', { ns: 'site' })} ${t('units', { ns: 'site' })}` : `${t('edit', { ns: 'site' })} ${t('units', { ns: 'site' })}`}</h1>

      {mutationError ? (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
          {getErrorMessage(mutationError, t('requestFailed', { ns: 'site' }))}
        </div>
      ) : null}

      <UnitForm
        siteId={effectiveSiteId}
        blockId={effectiveBlockId}
        floorId={effectiveFloorId}
        defaultValues={existing ?? undefined}
        submitLabel={mode === 'create' ? t('create', { ns: 'common' }) : t('save', { ns: 'common' })}
        isSubmitting={isSubmitting}
        serverFieldErrors={serverErrors}
        onSubmit={(values) => {
          setServerErrors({})
          const body = {
            site_id: values.site_id,
            block_id: values.block_id,
            floor_id: values.floor_id,
            unit_no: values.unit_no,
            type: values.type || undefined,
            gross_area: values.gross_area,
            net_area: values.net_area,
            land_share: values.land_share,
            occupant_name: values.occupant_name || undefined,
            status: values.status,
          }
          if (mode === 'create') {
            createMut.mutate(body, {
              onSuccess: (u) => {
                toast.success(`${t('units', { ns: 'site' })} ${t('create', { ns: 'common' })}`)
                navigate(`/units/${u.id}`)
              },
              onError: (e) => {
                setServerErrors(extractValidationErrors(e))
                toast.error(getErrorMessage(e, t('errorGeneric', { ns: 'common' })))
              },
            })
          } else if (unitId !== null && existing) {
            updateMut.mutate(
              { id: unitId, body },
              {
                onSuccess: (u) => {
                  toast.success(`${t('units', { ns: 'site' })} ${t('update', { ns: 'common' })}`)
                  navigate(`/units/${u.id}`)
                },
                onError: (e) => {
                  setServerErrors(extractValidationErrors(e))
                  toast.error(getErrorMessage(e, t('errorGeneric', { ns: 'common' })))
                },
              },
            )
          }
        }}
      />
    </div>
  )
}
