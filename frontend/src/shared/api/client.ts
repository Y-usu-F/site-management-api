import { ApiClientError, type ApiEnvelope } from '@/shared/api/types'

export interface ApiClientConfig {
  getBaseUrl: () => string
  getAccessToken: () => string | null
  getRefreshToken: () => string | null
  /** Persist new tokens after a successful `/auth/refresh`. */
  persistTokens: (data: Record<string, unknown>) => void
  /** Clear local session when refresh fails or `/auth/me` returns 401. */
  clearSession: () => void
  /**
   * Invoked when refresh fails or repeated 401 after retry.
   * Use for redirects / telemetry — refresh itself is handled internally on 401.
   */
  onSessionExpired?: () => void
}

let clientConfig: ApiClientConfig | null = null
let refreshInFlight: Promise<boolean> | null = null

export function configureApiClient(config: ApiClientConfig): void {
  clientConfig = config
}

export function getApiClientConfig(): ApiClientConfig | null {
  return clientConfig
}

async function parseJsonBody(response: Response): Promise<unknown | null> {
  const text = await response.text()
  if (!text.trim()) return null
  try {
    return JSON.parse(text) as unknown
  } catch {
    return null
  }
}

function isEnvelope(body: unknown): body is ApiEnvelope<unknown> {
  return (
    typeof body === 'object' &&
    body !== null &&
    'success' in body &&
    typeof (body as ApiEnvelope<unknown>).success === 'boolean'
  )
}

export function normalizeApiFailure(response: Response, body: unknown): ApiClientError {
  let message = response.statusText || 'Request failed'
  let errorCode: string | undefined
  let details: unknown
  let requestId: string | null | undefined

  if (isEnvelope(body)) {
    message = body.message || message
    requestId = body.meta?.request_id
    if (body.errors && typeof body.errors === 'object' && body.errors !== null) {
      const errors = body.errors as Record<string, unknown>
      if (typeof errors.error_code === 'string') {
        errorCode = errors.error_code
      }
      if ('details' in errors) {
        details = errors.details
      }
    }
  }

  return new ApiClientError({
    message,
    status: response.status,
    errorCode,
    details,
    requestId,
    rawBody: body,
  })
}

async function tryRefreshAccessToken(): Promise<boolean> {
  if (!clientConfig) return false
  if (refreshInFlight) return refreshInFlight

  refreshInFlight = (async () => {
    try {
      const refreshToken = clientConfig!.getRefreshToken()
      if (!refreshToken) return false

      const baseUrl = clientConfig!.getBaseUrl()
      const res = await fetch(`${baseUrl}/auth/refresh`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'Idempotency-Key': crypto.randomUUID(),
        },
        body: JSON.stringify({ refresh_token: refreshToken }),
      })
      const body = await parseJsonBody(res)
      if (!res.ok || !isEnvelope(body) || body.success !== true || body.data === null) {
        return false
      }
      clientConfig!.persistTokens(body.data as Record<string, unknown>)
      return true
    } catch {
      return false
    } finally {
      refreshInFlight = null
    }
  })()

  return refreshInFlight
}

export type ApiRequestOptions = RequestInit & {
  /** Skip `Authorization` header and refresh handling (public endpoints). */
  skipAuth?: boolean
}

/**
 * Typed JSON API call aligned with backend `api_response()` envelopes.
 */
export async function apiRequest<T>(
  path: string,
  init: ApiRequestOptions = {},
): Promise<T> {
  if (!clientConfig) {
    throw new Error('API client not configured — call configureApiClient() first.')
  }

  return execRequest<T>(path, init, false)
}

async function execRequest<T>(
  path: string,
  init: ApiRequestOptions,
  retried: boolean,
): Promise<T> {
  const cfg = clientConfig!
  const { skipAuth, ...requestInit } = init

  const url = `${cfg.getBaseUrl()}${path.startsWith('/') ? path : `/${path}`}`
  const headers = new Headers(requestInit.headers)

  headers.set('Accept', 'application/json')
  const method = (requestInit.method ?? 'GET').toUpperCase()
  if (
    ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) &&
    !headers.has('Idempotency-Key')
  ) {
    headers.set('Idempotency-Key', crypto.randomUUID())
  }
  if (!skipAuth) {
    const token = cfg.getAccessToken()
    if (token) {
      headers.set('Authorization', `Bearer ${token}`)
    }
  }

  if (
    requestInit.body !== undefined &&
    !(requestInit.body instanceof FormData) &&
    !headers.has('Content-Type')
  ) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(url, {
    ...requestInit,
    headers,
  })

  const body = await parseJsonBody(response)

  if (response.status === 401 && !skipAuth && !retried && cfg.getRefreshToken()) {
    const refreshed = await tryRefreshAccessToken()
    if (refreshed) {
      return execRequest<T>(path, init, true)
    }
    cfg.clearSession()
    cfg.onSessionExpired?.()
  }

  if (
    !response.ok ||
    (isEnvelope(body) && body.success === false)
  ) {
    throw normalizeApiFailure(response, body)
  }

  if (!isEnvelope(body)) {
    throw new ApiClientError({
      message: 'Unexpected API response shape',
      status: response.status,
      rawBody: body,
    })
  }

  return body.data as T
}
