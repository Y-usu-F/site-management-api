import { useQuery } from '@tanstack/react-query'

import { getResident } from '@/features/resident/api/residentApi'

export function residentDetailQueryKey(id: number) {
  return ['residents', 'detail', id] as const
}

export function useResidentQuery(id: number, enabled = true) {
  return useQuery({
    queryKey: residentDetailQueryKey(id),
    queryFn: () => getResident(id),
    enabled,
  })
}
