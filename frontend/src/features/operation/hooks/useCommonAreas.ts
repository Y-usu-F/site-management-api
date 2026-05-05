import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'

import {
  createCommonArea,
  deleteCommonArea,
  getCommonArea,
  listCommonAreas,
  updateCommonArea,
} from '@/features/operation/api/commonAreaApi'

export function useCommonAreasQuery(params: Record<string, unknown>, enabled = true) {
  return useQuery({
    queryKey: ['common-areas', params],
    queryFn: () => listCommonAreas(params),
    enabled,
  })
}

export function useCommonAreaQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: ['common-areas', 'detail', id],
    queryFn: () => getCommonArea(id),
    enabled,
  })
}

export function useCreateCommonAreaMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: Record<string, unknown>) => createCommonArea(body),
    onSuccess: () => void qc.invalidateQueries({ queryKey: ['common-areas'] }),
  })
}

export function useUpdateCommonAreaMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: Record<string, unknown> }) =>
      updateCommonArea(id, body),
    onSuccess: (_d, { id }) => {
      void qc.invalidateQueries({ queryKey: ['common-areas'] })
      void qc.invalidateQueries({ queryKey: ['common-areas', 'detail', id] })
    },
  })
}

export function useDeleteCommonAreaMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteCommonArea(id),
    onSuccess: (_d, id) => {
      void qc.invalidateQueries({ queryKey: ['common-areas'] })
      void qc.removeQueries({ queryKey: ['common-areas', 'detail', id] })
    },
  })
}
