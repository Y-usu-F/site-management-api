/**
 * Build `?a=1&b=two` from plain values; skips undefined, null, and empty strings.
 */
export function buildQueryString(
  params: Record<string, string | number | undefined | null>,
): string {
  const searchParams = new URLSearchParams()
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null) continue
    if (typeof value === 'string' && value.trim() === '') continue
    searchParams.set(key, String(value))
  }
  const qs = searchParams.toString()
  return qs === '' ? '' : `?${qs}`
}
