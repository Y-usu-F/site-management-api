<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\DashboardAnalyticsService;
use CodeIgniter\Test\CIUnitTestCase;

final class DashboardAnalyticsServiceTest extends CIUnitTestCase
{
    public function testSummaryRowdanBeklenenResponseShapeUretilir(): void
    {
        $service = new class extends DashboardAnalyticsService {
            protected function resolveCompanyId(): int
            {
                return 42;
            }

            protected function fetchSummaryRow(int $companyId): array
            {
                return [
                    'due_total' => '1250.50',
                    'paid_total' => '900.25',
                    'unpaid_total' => '350.25',
                    'payment_count' => '4',
                    'open_service_requests' => '3',
                    'active_work_orders' => 2,
                    'upcoming_reservations' => '1',
                    'resident_count' => '8',
                    'active_occupancy_count' => 5,
                    'unit_count' => '10',
                ];
            }
        };

        $summary = $service->summary();

        $this->assertSame(1250.5, $summary['finance']['due_total']);
        $this->assertSame(900.25, $summary['finance']['paid_total']);
        $this->assertSame(350.25, $summary['finance']['unpaid_total']);
        $this->assertSame(4, $summary['finance']['payment_count']);
        $this->assertSame(3, $summary['operations']['open_service_requests']);
        $this->assertSame(2, $summary['operations']['active_work_orders']);
        $this->assertSame(1, $summary['operations']['upcoming_reservations']);
        $this->assertSame(8, $summary['residents']['resident_count']);
        $this->assertSame(5, $summary['residents']['active_occupancy_count']);
        $this->assertSame(10, $summary['residents']['unit_count']);
    }
}
