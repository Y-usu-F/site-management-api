import { useMutation, useQueryClient } from '@tanstack/react-query'

import { createSite, deleteSite, updateSite } from '@/features/site/api/siteApi'
import type { SiteCreatePayload, SiteUpdatePayload } from '@/features/site/types'

import { siteDetailQueryKey } from '@/features/site/hooks/useSiteQuery'

export function useCreateSiteMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (body: SiteCreatePayload) => createSite(body),
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: ['sites'] })
    },
  })
}

export function useUpdateSiteMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, body }: { id: number; body: SiteUpdatePayload }) =>
      updateSite(id, body),
    onSuccess: (_data, { id }) => {
      void qc.invalidateQueries({ queryKey: ['sites'] })
      void qc.invalidateQueries({ queryKey: siteDetailQueryKey(id) })
    },
  })
}

export function useDeleteSiteMutation() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => deleteSite(id),
    onSuccess: (_data, id) => {
      void qc.invalidateQueries({ queryKey: ['sites'] })
      void qc.removeQueries({ queryKey: siteDetailQueryKey(id) })
      void qc.invalidateQueries({ queryKey: ['blocks'] })
      void qc.invalidateQueries({ queryKey: ['floors'] })
      void qc.invalidateQueries({ queryKey: ['units'] })
    },
  })
}
