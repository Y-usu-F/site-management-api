<?php

namespace App\Libraries;

final class ListQuery
{
    /**
     * @param array<string, mixed> $query
     * @param array{
     *   sortable:list<string>,
     *   filterable?:list<string>,
     *   default_sort?:string,
     *   default_direction?:'asc'|'desc',
     *   max_per_page?:int
     * } $config
     * @return array{
     *   page:int,
     *   per_page:int,
     *   search:string,
     *   sort:string,
     *   direction:'asc'|'desc',
     *   filters:array<string,mixed>
     * }
     */
    public static function normalize(array $query, array $config): array
    {
        $sortable = $config['sortable'];
        $filterable = $config['filterable'] ?? [];
        $defaultSort = $config['default_sort'] ?? 'id';
        $defaultDirection = strtolower((string) ($config['default_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $maxPerPage = max(1, (int) ($config['max_per_page'] ?? 100));

        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = max(1, min($maxPerPage, (int) ($query['per_page'] ?? 20)));
        $search = trim((string) ($query['search'] ?? ''));

        $sort = strtolower(trim((string) ($query['sort'] ?? $defaultSort)));
        if (! in_array($sort, $sortable, true)) {
            $sort = $defaultSort;
        }

        $direction = strtolower(trim((string) ($query['direction'] ?? $defaultDirection)));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $filters = [];
        foreach ($filterable as $field) {
            if (! array_key_exists($field, $query)) {
                continue;
            }
            $value = $query[$field];
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
            }
            if ($value === null) {
                continue;
            }
            $filters[$field] = $value;
        }

        return [
            'page' => $page,
            'per_page' => $perPage,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'filters' => $filters,
        ];
    }

    /**
     * @param list<array<string,mixed>> $items
     * @return array{page:int,per_page:int,total:int,total_pages:int,items:list<array<string,mixed>>}
     */
    public static function envelope(int $page, int $perPage, int $total, array $items): array
    {
        $totalPages = $total > 0 ? (int) ceil($total / max(1, $perPage)) : 0;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'items' => $items,
        ];
    }
}
