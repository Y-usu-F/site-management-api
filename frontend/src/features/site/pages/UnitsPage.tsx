import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQueryClient } from '@tanstack/react-query'

import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useFloorQuery } from '@/features/site/hooks/useFloorQuery'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
import { useUnitsQuery } from '@/features/site/hooks/useUnitsQuery'
import { bulkDeleteUnits, downloadUnitTemplate, exportUnitsExcel, importUnitsExcel } from '@/features/site/api/unitApi'
import { ApiClientError } from '@/shared/api/types'
import { BulkActionBar } from '@/shared/components/BulkActionBar'
import { ConfirmDialog } from '@/shared/components/ConfirmDialog'
import { EmptyState } from '@/shared/components/EmptyState'
import { ImportExcelDialog } from '@/shared/components/ImportExcelDialog'
import { PermissionDeniedNotice } from '@/shared/components/PermissionDeniedNotice'
import { useToast } from '@/shared/hooks/useToast'
import { getErrorMessage } from '@/shared/lib/extractValidationErrors'
import { formatDateTime } from '@/shared/lib/formatDateTime'
import { parsePositiveInt } from '@/shared/lib/parseRouteId'
import { useDebouncedValue } from '@/shared/hooks/useDebouncedValue'
import { useEffectiveCan } from '@/shared/hooks/useEffectiveCan'
import { useSelection } from '@/shared/hooks/useSelection'

const SEARCH_DEBOUNCE_MS = 350

export function UnitsPage() {
  const { t } = useTranslation(['site', 'common'])
  const toast = useToast()
  const qc = useQueryClient()
  const { floorId: floorIdRaw } = useParams<{ floorId: string }>()
  const floorId = parsePositiveInt(floorIdRaw)

  const canList = useEffectiveCan('unit.list')
  const canCreate = useEffectiveCan('unit.create')
  const canDelete = useEffectiveCan('unit.delete')
  const canExport = useEffectiveCan('unit.export')
  const canImport = useEffectiveCan('unit.import')
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)

  const { data: floor } = useFloorQuery(floorId ?? 0, {
    enabled: canList && floorId !== null,
  })

  const siteId = floor?.site_id ?? 0
  const blockId = floor?.block_id ?? 0

  const { data: site } = useSiteQuery(siteId, { enabled: siteId > 0 })
  const { data: block } = useBlockQuery(blockId, { enabled: blockId > 0 })

  const [searchInput, setSearchInput] = useState('')
  const debouncedSearch = useDebouncedValue(searchInput, SEARCH_DEBOUNCE_MS)

  const filterKey =
    debouncedSearch.trim() === '' ? '__all__' : debouncedSearch.trim()
  const [pagesByFilter, setPagesByFilter] = useState<Record<string, number>>({})
  const page = pagesByFilter[filterKey] ?? 1
  const setPage = (next: number | ((prev: number) => number)) => {
    setPagesByFilter((prev) => {
      const current = prev[filterKey] ?? 1
      const resolved = typeof next === 'function' ? next(current) : next
      return { ...prev, [filterKey]: resolved }
    })
  }

  const params = useMemo(
    () => ({
      page,
      per_page: 20,
      search: debouncedSearch.trim() === '' ? undefined : debouncedSearch.trim(),
      floor_id: floorId ?? 0,
    }),
    [page, debouncedSearch, floorId],
  )

  const { data, isPending, isError, error, isFetching } = useUnitsQuery(params, {
    enabled: canList && floorId !== null,
  })

  const items = data?.items ?? []
  const pageIds = items.map((x) => x.id)
  const { selectedIds, selectedCount, allSelected, toggleOne, toggleAllCurrentPage, clearSelection } =
    useSelection(pageIds)
  const totalPages = data?.total_pages ?? 0

  const bulkDeleteMutation = useMutation({
    mutationFn: (ids: number[]) => bulkDeleteUnits(ids),
    onSuccess: () => {
      toast.success(t('bulkDeleteUnitsSuccess', { ns: 'site' }))
      clearSelection()
      void qc.invalidateQueries({ queryKey: ['units'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('bulkDeleteEndpointNotAvailableYet', { ns: 'site' }))
        return
      }
      toast.error(getErrorMessage(err, t('couldNotDeleteSelectedUnits', { ns: 'site' })))
    },
  })
  const exportMutation = useMutation({
    mutationFn: () => exportUnitsExcel(params),
    onSuccess: () => toast.success(t('exportStarted', { ns: 'site' })),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('exportEndpointNotAvailableYet', { ns: 'site' }))
        return
      }
      toast.error(getErrorMessage(err, t('couldNotExportUnits', { ns: 'site' })))
    },
  })
  const templateMutation = useMutation({
    mutationFn: () => downloadUnitTemplate(),
    onSuccess: () => toast.success(t('templateDownloaded', { ns: 'site' })),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('templateEndpointNotAvailableYet', { ns: 'site' }))
        return
      }
      toast.error(getErrorMessage(err, t('couldNotDownloadTemplate', { ns: 'site' })))
    },
  })
  const importMutation = useMutation({
    mutationFn: (file: File) => importUnitsExcel(file),
    onSuccess: (result) => {
      toast.success(
        t('importDone', { ns: 'site', inserted: result.inserted_count ?? 0,
          updated: result.updated_count ?? 0,
          skipped: result.skipped_count ?? 0, }),
      )
      setImportOpen(false)
      void qc.invalidateQueries({ queryKey: ['units'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('importEndpointNotAvailableYet', { ns: 'site' }))
        return
      }
      toast.error(getErrorMessage(err, t('couldNotImportUnits', { ns: 'site' })))
    },
  })

  if (!canList) {
    return <PermissionDeniedNotice permission="unit.list" />
  }

  if (floorId === null) {
    return (
      <p className="text-sm">
        {t('invalidFloor', { ns: 'site' })} <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
      </p>
    )
  }

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('sites', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${siteId}`}>{site?.code ?? siteId}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${siteId}/blocks`}>{t('blocks', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${blockId}`}>{block?.code ?? blockId}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${blockId}/floors`}>{t('floors', { ns: 'site' })}</Link>
        <span className="mx-1">/</span>
        <Link to={`/floors/${floorId}`}>{t('floorWithNumber', { ns: 'site', number: floor?.number ?? '' })}</Link>
        <span className="mx-1">/</span>
        <span>{t('units', { ns: 'site' })}</span>
      </nav>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{t('units', { ns: 'site' })}</h1>
          <p className="mt-1 text-sm text-zinc-600">{t('unitsOnFloor', { ns: 'site', count: data?.total ?? 0 })}</p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            type="search"
            placeholder={t('searchUnitNoOrOccupant', { ns: 'site' })}
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            className="w-full rounded-lg border px-3 py-2 text-sm sm:w-72 dark:border-zinc-600 dark:bg-zinc-950"
          />
          {canCreate ? (
            <Link
              to={`/floors/${floorId}/units/new`}
              className="inline-flex justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white"
            >
              {t('new', { ns: 'site' })}
            </Link>
          ) : null}
        </div>
      </div>
      <BulkActionBar
        selectedCount={selectedCount}
        canDelete={canDelete}
        canExport={canExport}
        canImport={canImport}
        isBulkDeleting={bulkDeleteMutation.isPending}
        isExporting={exportMutation.isPending || templateMutation.isPending}
        onBulkDelete={() => setDeleteConfirmOpen(true)}
        onExport={() => exportMutation.mutate()}
        onImport={() => setImportOpen(true)}
        onTemplateDownload={() => templateMutation.mutate()}
        onClearSelection={clearSelection}
      />

      {isPending ? (
        <div className="rounded-xl border p-12 text-center text-sm">{t('loading', { ns: 'site' })}</div>
      ) : null}

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
          {error instanceof Error ? error.message : t('errorGeneric', { ns: 'common' })}
        </div>
      ) : null}

      {!isPending && !isError && items.length === 0 ? (
        <EmptyState
          title={t('emptyTitle', { ns: 'common' })}
          description={
            canCreate ? (
              <Link className="text-violet-600 underline" to={`/floors/${floorId}/units/new`}>
                {t('createOne', { ns: 'site' })}
              </Link>
            ) : (
              t('createPermissionRequiredForUnits', { ns: 'site' })
            )
          }
        />
      ) : null}

      {!isPending && !isError && items.length > 0 ? (
        <div className="overflow-hidden rounded-xl border bg-white dark:border-zinc-800 dark:bg-zinc-900">
          <table className="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead className="bg-zinc-50 dark:bg-zinc-800/80">
              <tr>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">
                  <input
                    type="checkbox"
                    checked={allSelected}
                    onChange={toggleAllCurrentPage}
                    aria-label={t('selectAllUnitsOnPage', { ns: 'site' })}
                  />
                </th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('form.unitNumber', { ns: 'site' })}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('form.type', { ns: 'site' })}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('area', { ns: 'site' })}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('status', { ns: 'site' })}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('updated', { ns: 'site' })}</th>
                <th className="px-4 py-3 text-right text-xs uppercase text-zinc-500">{t('actions', { ns: 'site' })}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-zinc-200 dark:divide-zinc-700">
              {items.map((row) => (
                <tr key={row.id}>
                  <td className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={selectedIds.includes(row.id)}
                      onChange={() => toggleOne(row.id)}
                      aria-label={t('selectUnitWithNo', { ns: 'site', no: row.unit_no })}
                    />
                  </td>
                  <td className="px-4 py-3 font-mono text-sm">{row.unit_no}</td>
                  <td className="px-4 py-3 text-sm">{row.type?.trim() ? row.type : '—'}</td>
                  <td className="px-4 py-3 text-xs text-zinc-600">
                    {t('grossShort', { ns: 'site' })} {row.gross_area ?? '—'} / {t('netShort', { ns: 'site' })} {row.net_area ?? '—'}
                    {row.land_share != null && row.land_share !== '' ? (
                      <span className="block">{t('land', { ns: 'site' })} {String(row.land_share)}</span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 text-sm">{row.status}</td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {formatDateTime(row.updated_at ?? row.created_at)}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Link className="text-violet-600" to={`/units/${row.id}`}>
                      {t('open', { ns: 'site' })}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {totalPages > 1 ? (
            <div className="flex justify-between border-t px-4 py-3 text-xs">
              <span>
                {t('pagination.page', { ns: 'common' })} {data?.page ?? page} {t('pagination.of', { ns: 'common' })} {totalPages}
                {isFetching ? ` · ${t('pagination.refreshing', { ns: 'common' })}` : ''}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  className="rounded border px-2 py-1 disabled:opacity-40"
                >
                  {t('pagination.prev', { ns: 'common' })}
                </button>
                <button
                  type="button"
                  disabled={page >= totalPages}
                  onClick={() => setPage((p) => p + 1)}
                  className="rounded border px-2 py-1 disabled:opacity-40"
                >
                  {t('pagination.next', { ns: 'common' })}
                </button>
              </div>
            </div>
          ) : null}
        </div>
      ) : null}
      <ConfirmDialog
        isOpen={deleteConfirmOpen}
        title={t('deleteSelectedUnitsTitle', { ns: 'site' })}
        description={t('deleteSelectedUnitsDescription', { ns: 'site', count: selectedCount })}
        confirmText={t('delete', { ns: 'site' })}
        cancelText={t('cancel', { ns: 'common' })}
        variant="danger"
        isLoading={bulkDeleteMutation.isPending}
        onClose={() => setDeleteConfirmOpen(false)}
        onConfirm={() => bulkDeleteMutation.mutate(selectedIds)}
      />
      <ImportExcelDialog
        isOpen={importOpen}
        title={t('importUnitsFromExcel', { ns: 'site' })}
        isSubmitting={importMutation.isPending}
        onClose={() => setImportOpen(false)}
        onSubmit={(file) => importMutation.mutate(file)}
      />
    </div>
  )
}
