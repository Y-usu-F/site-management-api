import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import { createAsset, getAsset, listAssets, updateAsset } from '@/features/operation/api/assetApi'

export function useAssetsQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['assets', params],
    queryFn: () => listAssets(params),
    enabled,
  })
}

export function useAssetQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['assets', 'detail', id],
    queryFn: () => getAsset(id),
    enabled,
  })
}

export function useCreateAssetMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createAsset(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['assets'] }),
  })
}

export function useUpdateAssetMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateAsset(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['assets'] })
      void qc.invalidateQueries({ queryKey: ['assets', 'detail', id] })
    },
  })
}
