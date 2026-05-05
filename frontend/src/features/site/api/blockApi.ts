import type {
  Block,
  BlockCreatePayload,
  BlockListParams,
  BlockListResponse,
  BlockUpdatePayload,
} from '@/features/site/types'
import { apiRequest } from '@/shared/api/client'
import { downloadFromApi } from '@/shared/api/fileDownload'
import { buildQueryString } from '@/shared/lib/buildQueryString'
import type { ImportResult } from '@/features/site/api/siteApi'

export async function listBlocks(params: BlockListParams): Promise<BlockListResponse> {
  const qs = buildQueryString({
    page: params.page,
    per_page: params.per_page,
    search: params.search?.trim() ? params.search.trim() : undefined,
    site_id: params.site_id,
  })
  return apiRequest<BlockListResponse>(`/blocks${qs}`)
}

export async function getBlock(id: number): Promise<Block> {
  return apiRequest<Block>(`/blocks/${id}`)
}

export async function createBlock(body: BlockCreatePayload): Promise<Block> {
  return apiRequest<Block>('/blocks', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateBlock(id: number, body: BlockUpdatePayload): Promise<Block> {
  return apiRequest<Block>(`/blocks/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteBlock(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/blocks/${id}`, {
    method: 'DELETE',
  })
}

export async function bulkDeleteBlocks(ids: number[]): Promise<{ ids: number[] }> {
  return apiRequest<{ ids: number[] }>('/blocks/bulk', {
    method: 'DELETE',
    body: JSON.stringify({ ids }),
  })
}

export async function exportBlocksExcel(params: BlockListParams): Promise<void> {
  const qs = buildQueryString({
    site_id: params.site_id,
    search: params.search?.trim() ? params.search.trim() : undefined,
    page: params.page,
    per_page: params.per_page,
  })
  await downloadFromApi(`/blocks/export${qs}`, 'blocks.xlsx')
}

export async function importBlocksExcel(file: File): Promise<ImportResult> {
  const form = new FormData()
  form.append('file', file)
  return apiRequest<ImportResult>('/blocks/import', {
    method: 'POST',
    body: form,
  })
}

export async function downloadBlockTemplate(): Promise<void> {
  await downloadFromApi('/blocks/import-template', 'blocks-import-template.xlsx')
}
