import { ApiClientError } from '@/shared/api/types'

/** Maps backend validation `details` (CodeIgniter-style) into field → message. */
export function extractValidationErrors(err: unknown): Record<string, string> {
  if (!(err instanceof ApiClientError)) return {}
  const details = err.details
  if (!details || typeof details !== 'object') return {}
  const out: Record<string, string> = {}
  for (const [key, raw] of Object.entries(details)) {
    if (typeof raw === 'string') {
      out[key] = raw
      continue
    }
    if (Array.isArray(raw) && raw.length > 0) {
      const first = raw[0]
      out[key] = typeof first === 'string' ? first : String(first)
    }
  }
  return out
}

export function getErrorMessage(err: unknown, fallback = 'Islem basarisiz.'): string {
  if (err instanceof Error && err.message.trim() !== '') {
    return err.message
  }
  return fallback
}
