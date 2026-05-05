export interface ApiMeta {
  request_id?: string | null
}

export interface ApiEnvelope<T> {
  success: boolean
  message: string
  data: T
  errors: unknown
  meta?: ApiMeta
}

export interface ApiErrorPayload {
  message: string
  status: number
  errorCode?: string
  details?: unknown
  requestId?: string | null
  rawBody: unknown
}

export class ApiClientError extends Error {
  readonly status: number
  readonly errorCode?: string
  readonly details?: unknown
  readonly requestId?: string | null
  readonly rawBody: unknown

  constructor(payload: ApiErrorPayload) {
    super(payload.message)
    this.name = 'ApiClientError'
    this.status = payload.status
    this.errorCode = payload.errorCode
    this.details = payload.details
    this.requestId = payload.requestId
    this.rawBody = payload.rawBody
  }
}
