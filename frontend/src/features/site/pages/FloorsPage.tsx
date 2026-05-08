import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { useMutation, useQueryClient } from '@tanstack/react-query'

import { bulkDeleteFloors, downloadFloorTemplate, exportFloorsExcel, importFloorsExcel } from '@/features/site/api/floorApi'
import { useBlockQuery } from '@/features/site/hooks/useBlockQuery'
import { useFloorsQuery } from '@/features/site/hooks/useFloorsQuery'
import { useSiteQuery } from '@/features/site/hooks/useSiteQuery'
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

export function FloorsPage() {
  const { t } = useTranslation(['site', 'common'])
  const toast = useToast()
  const qc = useQueryClient()
  const { blockId: blockIdRaw } = useParams<{ blockId: string }>()
  const blockId = parsePositiveInt(blockIdRaw)

  const canList = useEffectiveCan('floor.list')
  const canCreate = useEffectiveCan('floor.create')
  const canDelete = useEffectiveCan('floor.delete')
  const canExport = useEffectiveCan('floor.export')
  const canImport = useEffectiveCan('floor.import')
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false)
  const [importOpen, setImportOpen] = useState(false)

  const { data: block } = useBlockQuery(blockId ?? 0, {
    enabled: canList && blockId !== null,
  })

  const siteId = block?.site_id ?? 0
  const { data: site } = useSiteQuery(siteId, {
    enabled: siteId > 0,
  })

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
      block_id: blockId ?? 0,
    }),
    [page, debouncedSearch, blockId],
  )

  const { data, isPending, isError, error, isFetching } = useFloorsQuery(params, {
    enabled: canList && blockId !== null,
  })

  const items = data?.items ?? []
  const pageIds = items.map((x) => x.id)
  const { selectedIds, selectedCount, allSelected, toggleOne, toggleAllCurrentPage, clearSelection } =
    useSelection(pageIds)
  const totalPages = data?.total_pages ?? 0

  const bulkDeleteMutation = useMutation({
    mutationFn: (ids: number[]) => bulkDeleteFloors(ids),
    onSuccess: () => {
      toast.success(t('site.common.bulkDeleteFloorsSuccess'))
      clearSelection()
      void qc.invalidateQueries({ queryKey: ['floors'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.bulkDeleteEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotDeleteSelectedFloors')))
    },
  })
  const exportMutation = useMutation({
    mutationFn: () => exportFloorsExcel(params),
    onSuccess: () => toast.success(t('site.common.exportStarted')),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.exportEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotExportFloors')))
    },
  })
  const templateMutation = useMutation({
    mutationFn: () => downloadFloorTemplate(),
    onSuccess: () => toast.success(t('site.common.templateDownloaded')),
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.templateEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotDownloadTemplate')))
    },
  })
  const importMutation = useMutation({
    mutationFn: (file: File) => importFloorsExcel(file),
    onSuccess: (result) => {
      toast.success(
        t('site.common.importDone', {
          inserted: result.inserted_count ?? 0,
          updated: result.updated_count ?? 0,
          skipped: result.skipped_count ?? 0,
        }),
      )
      setImportOpen(false)
      void qc.invalidateQueries({ queryKey: ['floors'] })
    },
    onError: (err) => {
      if (err instanceof ApiClientError && (err.status === 404 || err.status === 405)) {
        toast.error(t('site.common.importEndpointNotAvailableYet'))
        return
      }
      toast.error(getErrorMessage(err, t('site.common.couldNotImportFloors')))
    },
  })

  if (!canList) {
    return <PermissionDeniedNotice permission="floor.list" />
  }

  if (blockId === null) {
    return (
      <p className="text-sm">
        Invalid block. <Link to="/sites">Sites</Link>
      </p>
    )
  }

  return (
    <div className="space-y-6">
      <nav className="text-xs text-zinc-500">
        <Link to="/sites">{t('site.common.sites')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${siteId}`}>{site?.code ?? `Site ${siteId}`}</Link>
        <span className="mx-1">/</span>
        <Link to={`/sites/${siteId}/blocks`}>{t('site.common.blocks')}</Link>
        <span className="mx-1">/</span>
        <Link to={`/blocks/${blockId}`}>{block?.code ?? `Block ${blockId}`}</Link>
        <span className="mx-1">/</span>
        <span>{t('site.common.floors')}</span>
      </nav>

      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-semibold">{t('site.common.floors')}</h1>
          <p className="mt-1 text-sm text-zinc-600">{data?.total ?? 0} floor(s).</p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <input
            type="search"
            placeholder="Search label or number…"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            className="w-full rounded-lg border px-3 py-2 text-sm sm:w-72 dark:border-zinc-600 dark:bg-zinc-950"
          />
          {canCreate ? (
            <Link
              to={`/blocks/${blockId}/floors/new`}
              className="inline-flex justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white"
            >
              {t('site.common.new')}
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
        <div className="rounded-xl border p-12 text-center text-sm">{t('site.common.loading')}</div>
      ) : null}

      {isError ? (
        <div className="rounded-xl border border-red-200 bg-red-50 p-6 text-sm text-red-800">
          {error instanceof Error ? error.message : t('common.errorGeneric')}
        </div>
      ) : null}

      {!isPending && !isError && items.length === 0 ? (
        <EmptyState
          title={t('common.emptyTitle')}
          description={
            canCreate ? (
              <Link className="text-violet-600 underline" to={`/blocks/${blockId}/floors/new`}>
                Add floor
              </Link>
            ) : (
              'Create permission required to add floors.'
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
                    aria-label="Select all floors on page"
                  />
                </th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">#</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('site.form.floorLabel')}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('site.common.status')}</th>
                <th className="px-4 py-3 text-left text-xs uppercase text-zinc-500">{t('site.common.updated')}</th>
                <th className="px-4 py-3 text-right text-xs uppercase text-zinc-500">{t('site.common.actions')}</th>
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
                      aria-label={`Select floor ${row.number}`}
                    />
                  </td>
                  <td className="px-4 py-3 font-mono text-sm">{row.number}</td>
                  <td className="px-4 py-3 text-sm">{row.label?.trim() ? row.label : '—'}</td>
                  <td className="px-4 py-3 text-sm">{row.status}</td>
                  <td className="px-4 py-3 text-sm text-zinc-500">
                    {formatDateTime(row.updated_at ?? row.created_at)}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Link className="text-violet-600" to={`/floors/${row.id}`}>
                      Open
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {totalPages > 1 ? (
            <div className="flex justify-between border-t px-4 py-3 text-xs">
              <span>
                Page {data?.page ?? page} / {totalPages}
                {isFetching ? ' …' : ''}
              </span>
              <div className="flex gap-2">
                <button
                  type="button"
                  disabled={page <= 1}
                  onClick={() => setPage((p) => Math.max(1, p - 1))}
                  className="rounded border px-2 py-1 disabled:opacity-40"
                >
                  Prev
                </button>
                <button
                  type="button"
                  disabled={page >= totalPages}
                  onClick={() => setPage((p) => p + 1)}
                  className="rounded border px-2 py-1 disabled:opacity-40"
                >
                  Next
                </button>
              </div>
            </div>
          ) : null}
        </div>
      ) : null}
      <ConfirmDialog
        isOpen={deleteConfirmOpen}
        title={t('site.common.deleteSelectedFloorsTitle')}
        description={t('site.common.deleteSelectedFloorsDescription', { count: selectedCount })}
        confirmText={t('site.common.delete')}
        cancelText={t('common.cancel')}
        variant="danger"
        isLoading={bulkDeleteMutation.isPending}
        onClose={() => setDeleteConfirmOpen(false)}
        onConfirm={() => bulkDeleteMutation.mutate(selectedIds)}
      />
      <ImportExcelDialog
        isOpen={importOpen}
        title={t('site.common.importFloorsFromExcel')}
        isSubmitting={importMutation.isPending}
        onClose={() => setImportOpen(false)}
        onSubmit={(file) => importMutation.mutate(file)}
      />
    </div>
  )
}
