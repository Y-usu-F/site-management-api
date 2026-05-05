import { useAuthStore } from '@/features/auth/auth.store'
import type { LoginResponsePayload, MeResponse } from '@/features/auth/auth.store'
import { apiRequest } from '@/shared/api/client'

export async function loginRequest(
  email: string,
  password: string,
): Promise<LoginResponsePayload> {
  return apiRequest<LoginResponsePayload>('/auth/login', {
    method: 'POST',
    headers: {
      'Idempotency-Key': crypto.randomUUID(),
    },
    body: JSON.stringify({ email, password }),
    skipAuth: true,
  })
}

export async function logoutRequest(): Promise<void> {
  await apiRequest<unknown>('/auth/logout', {
    method: 'POST',
    headers: {
      'Idempotency-Key': crypto.randomUUID(),
    },
    body: JSON.stringify({}),
  })
}

export async function fetchAuthMe(): Promise<MeResponse> {
  return apiRequest<MeResponse>('/auth/me')
}

/** Loads permission codes when `/auth/me` returns them (currently often empty server-side). */
export async function tryHydrateAuthProfile(): Promise<void> {
  try {
    const me = await fetchAuthMe()
    useAuthStore.getState().mergeMe(me)
  } catch {
    // Missing `auth.me.view` or network — session still valid from login.
  }
}
