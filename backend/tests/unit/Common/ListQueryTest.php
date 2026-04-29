<?php

namespace Tests\Unit\Common;

use App\Libraries\ListQuery;
use CodeIgniter\Test\CIUnitTestCase;

final class ListQueryTest extends CIUnitTestCase
{
    public function testNormalizePaginationSearchSortVeWhitelistCalisir(): void
    {
        $normalized = ListQuery::normalize([
            'page' => '2',
            'per_page' => '250',
            'search' => ' ali ',
            'sort' => 'name',
            'direction' => 'asc',
            'status' => 'active',
            'unknown' => 'ignored',
        ], [
            'sortable' => ['id', 'name'],
            'filterable' => ['status'],
            'default_sort' => 'id',
            'default_direction' => 'desc',
            'max_per_page' => 100,
        ]);

        $this->assertSame(2, $normalized['page']);
        $this->assertSame(100, $normalized['per_page']);
        $this->assertSame('ali', $normalized['search']);
        $this->assertSame('name', $normalized['sort']);
        $this->assertSame('asc', $normalized['direction']);
        $this->assertSame(['status' => 'active'], $normalized['filters']);
    }

    public function testInvalidSortDirectionFallbackeDuser(): void
    {
        $normalized = ListQuery::normalize([
            'sort' => 'drop table',
            'direction' => 'xxx',
        ], [
            'sortable' => ['id', 'created_at'],
            'default_sort' => 'created_at',
            'default_direction' => 'desc',
        ]);

        $this->assertSame('created_at', $normalized['sort']);
        $this->assertSame('desc', $normalized['direction']);
    }

    public function testEnvelopeTotalPagesHesaplar(): void
    {
        $envelope = ListQuery::envelope(1, 20, 100, []);
        $this->assertSame(5, $envelope['total_pages']);

        $empty = ListQuery::envelope(1, 20, 0, []);
        $this->assertSame(0, $empty['total_pages']);
    }
}
