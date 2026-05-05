import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface AuthUser {
  id: number
  company_id: number
  email: string
  roles: string[]
}

export interface LoginResponsePayload {
  access_token: string
  refresh_token: string
  expires_in?: number
  refresh_expires_in?: number
  token_type?: string
  user: AuthUser
}

/** Payload from `GET /auth/me` after FRONTEND-003 (nested user + company_id + permissions). */
export interface MeResponse {
  user: {
    id: number
    email: string
    roles: string[]
  }
  company_id: number
  permissions: string[]
}

interface AuthState {
  accessToken: string | null
  refreshToken: string | null
  user: AuthUser | null
  permissions: string[]
  expiresAt: number | null

  setFromLogin: (data: LoginResponsePayload) => void
  applyTokenRotation: (data: Record<string, unknown>) => void
  mergeMe: (me: MeResponse) => void
  clearSession: () => void
}

function pickUser(raw: AuthUser): AuthUser {
  return {
    id: raw.id,
    company_id: raw.company_id,
    email: raw.email,
    roles: Array.isArray(raw.roles) ? raw.roles : [],
  }
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      accessToken: null,
      refreshToken: null,
      user: null,
      permissions: [],
      expiresAt: null,

      setFromLogin: (data) =>
        set({
          accessToken: data.access_token,
          refreshToken: data.refresh_token,
          user: pickUser(data.user),
          permissions: [],
          expiresAt:
            typeof data.expires_in === 'number'
              ? Date.now() + data.expires_in * 1000
              : null,
        }),

      applyTokenRotation: (data) =>
        set((state) => ({
          accessToken:
            typeof data.access_token === 'string'
              ? data.access_token
              : state.accessToken,
          refreshToken:
            typeof data.refresh_token === 'string'
              ? data.refresh_token
              : state.refreshToken,
          expiresAt:
            typeof data.expires_in === 'number'
              ? Date.now() + data.expires_in * 1000
              : state.expiresAt,
        })),

      mergeMe: (me) =>
        set({
          user: pickUser({
            id: me.user.id,
            company_id: me.company_id,
            email: me.user.email,
            roles: me.user.roles,
          }),
          permissions: me.permissions.map((p) => p.trim().toLowerCase()),
        }),

      clearSession: () =>
        set({
          accessToken: null,
          refreshToken: null,
          user: null,
          permissions: [],
          expiresAt: null,
        }),
    }),
    {
      name: 'site-management-auth',
      partialize: (state) => ({
        accessToken: state.accessToken,
        refreshToken: state.refreshToken,
        user: state.user,
        permissions: state.permissions,
        expiresAt: state.expiresAt,
      }),
    },
  ),
)
