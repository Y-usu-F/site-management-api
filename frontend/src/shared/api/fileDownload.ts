import { ApiClientError } from '@/shared/api/types'
import { getApiClientConfig } from '@/shared/api/client'

function createHeaders(contentType?: string): Headers {
  const cfg = getApiClientConfig()
  if (!cfg) {
    throw new Error('API client not configured.')
  }
  const headers = new Headers()
  const token = cfg.getAccessToken()
  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }
  if (contentType) {
    headers.set('Content-Type', contentType)
  }
  headers.set('Accept', '*/*')
  return headers
}

function saveBlob(blob: Blob, filename: string) {
  const href = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = href
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  URL.revokeObjectURL(href)
}

export async function downloadFromApi(path: string, filename: string): Promise<void> {
  const cfg = getApiClientConfig()
  if (!cfg) {
    throw new Error('API client not configured.')
  }
  const res = await fetch(`${cfg.getBaseUrl()}${path.startsWith('/') ? path : `/${path}`}`, {
    method: 'GET',
    headers: createHeaders(),
  })
  if (!res.ok) {
    throw new ApiClientError({
      message: res.statusText || 'Download failed',
      status: res.status,
      rawBody: null,
    })
  }
  const blob = await res.blob()
  saveBlob(blob, filename)
}
