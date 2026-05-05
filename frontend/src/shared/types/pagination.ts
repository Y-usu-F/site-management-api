/** Matches backend `ListQuery::envelope` list payloads. */
export interface PaginatedResponse<T> {
  page: number
  per_page: number
  total: number
  total_pages: number
  items: T[]
}
