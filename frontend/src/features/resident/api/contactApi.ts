import { apiRequest } from '@/shared/api/client'
import { buildQueryString } from '@/shared/lib/buildQueryString'

import type {
  ContactListParams,
  ContactListResponse,
  ContactPayload,
  ResidentContact,
} from '@/features/resident/types'

export async function listResidentContacts(
  params: ContactListParams,
): Promise<ContactListResponse> {
  const qs = buildQueryString({
    page: params.page,
    per_page: params.per_page,
    resident_profile_id: params.resident_profile_id,
  })
  return apiRequest<ContactListResponse>(`/resident-contacts${qs}`)
}

export async function createResidentContact(body: ContactPayload): Promise<ResidentContact> {
  return apiRequest<ResidentContact>('/resident-contacts', {
    method: 'POST',
    body: JSON.stringify(body),
  })
}

export async function updateResidentContact(
  id: number,
  body: Partial<ContactPayload>,
): Promise<ResidentContact> {
  return apiRequest<ResidentContact>(`/resident-contacts/${id}`, {
    method: 'PUT',
    body: JSON.stringify(body),
  })
}

export async function deleteResidentContact(id: number): Promise<{ id: number }> {
  return apiRequest<{ id: number }>(`/resident-contacts/${id}`, {
    method: 'DELETE',
  })
}
