<?php

namespace Tests\Unit\Services\Operation;

use App\Services\Operation\OperationsSummaryService;
use CodeIgniter\Test\CIUnitTestCase;

final class OperationsSummaryServiceTest extends CIUnitTestCase
{
    public function testSummaryRowdanBeklenenResponseShapeUretilir(): void
    {
        $service = new class extends OperationsSummaryService {
            protected function resolveCompanyId(): int
            {
                return 99;
            }

            protected function fetchSummaryRow(int $companyId): array
            {
                return [
                    'service_requests_open' => '4',
                    'work_orders_in_progress' => 3,
                    'reservations_pending' => '2',
                    'reservations_approved' => 1,
                    'maintenance_active_plans' => '5',
                ];
            }
        };

        $summary = $service->summary();

        $this->assertSame(4, $summary['service_requests']['open']);
        $this->assertSame(3, $summary['work_orders']['in_progress']);
        $this->assertSame(2, $summary['reservations']['pending']);
        $this->assertSame(1, $summary['reservations']['approved']);
        $this->assertSame(5, $summary['maintenance']['active_plans']);
    }
}

