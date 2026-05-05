import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  ResidentVehicle,
  VehicleListParams,
  VehicleListResponse,
  VehiclePayload,
} from '@/features/resident/types'

export async function listResidentVehicles(
  params: VehicleListParams,
): Promise<VehicleListResponse> {
  const qs = buildQueryString({
    page: params.page,
    per_page: params.per_page,
    resident_profile_id: params.resident_profile_id,
    status: params.status,
  })
  return apiRequest<VehicleListResponse>(`/resident-vehicles${qs}`)
}

export async function getResidentVehicle(id: number): Promise<ResidentVehicle> {
  return apiRequest<ResidentVehicle>(`/resident-vehicles/${id}`)
}

export async function createResidentVehicle(body: VehiclePayload): Promise<ResidentVehicle> {
  return apiRequest<ResidentVehicle>('/resident-vehicles', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateResidentVehicle(
  id: number,
  body: Partial<VehiclePayload>,
): Promise<ResidentVehicle> {
  return apiRequest<ResidentVehicle>(`/resident-vehicles/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteResidentVehicle(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/resident-vehicles/${id}`, {
    method: 'DELETE',
  })
}
