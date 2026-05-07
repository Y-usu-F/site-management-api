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

            protected function buildPaymentsTrend(int $companyId): array
            {
                return [
                    ['date' => '2026-05-01', 'total' => 100.0],
                    ['date' => '2026-05-02', 'total' => 200.0],
                ];
            }

            protected function buildServiceRequestsTrend(int $companyId): array
            {
                return [
                    ['date' => '2026-05-01', 'count' => 2],
                    ['date' => '2026-05-02', 'count' => 0],
                ];
            }

            protected function fetchStatusDistribution(int $companyId, string $table): array
            {
                if ($table === 'service_requests') {
                    return [
                        ['status' => 'open', 'count' => 3],
                    ];
                }

                return [
                    ['status' => 'in_progress', 'count' => 2],
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
        $this->assertSame(2, count($summary['trends']['payments_last_30_days']));
        $this->assertSame(100.0, $summary['trends']['payments_last_30_days'][0]['total']);
        $this->assertSame(2, $summary['trends']['service_requests_last_30_days'][0]['count']);
        $this->assertSame('open', $summary['distributions']['service_request_statuses'][0]['status']);
        $this->assertSame(2, $summary['distributions']['work_order_statuses'][0]['count']);
    }
}
