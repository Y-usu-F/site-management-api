<?php

namespace App\Services\Analytics;

use App\Support\RequestRuntime;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly ?BaseConnection $db = null
    ) {
    }

    /**
     * @return array{
     *   finance: array{due_total:float,paid_total:float,unpaid_total:float,payment_count:int},
     *   operations: array{open_service_requests:int,active_work_orders:int,upcoming_reservations:int},
     *   residents: array{resident_count:int,active_occupancy_count:int,unit_count:int}
     * }
     */
    public function summary(): array
    {
        $companyId = $this->resolveCompanyId();
        if ($companyId <= 0) {
            return $this->emptySummary();
        }

        $row = $this->fetchSummaryRow($companyId);

        return [
            'finance' => [
                'due_total' => (float) ($row['due_total'] ?? 0),
                'paid_total' => (float) ($row['paid_total'] ?? 0),
                'unpaid_total' => (float) ($row['unpaid_total'] ?? 0),
                'payment_count' => (int) ($row['payment_count'] ?? 0),
            ],
            'operations' => [
                'open_service_requests' => (int) ($row['open_service_requests'] ?? 0),
                'active_work_orders' => (int) ($row['active_work_orders'] ?? 0),
                'upcoming_reservations' => (int) ($row['upcoming_reservations'] ?? 0),
            ],
            'residents' => [
                'resident_count' => (int) ($row['resident_count'] ?? 0),
                'active_occupancy_count' => (int) ($row['active_occupancy_count'] ?? 0),
                'unit_count' => (int) ($row['unit_count'] ?? 0),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function fetchSummaryRow(int $companyId): array
    {
        $sql = "SELECT
            (SELECT COALESCE(SUM(amount), 0) FROM due_items WHERE company_id = ? AND deleted_at IS NULL AND status != 'cancelled') AS due_total,
            (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE company_id = ? AND deleted_at IS NULL AND status = 'completed') AS paid_total,
            (SELECT COALESCE(SUM(remaining_amount), 0) FROM due_items WHERE company_id = ? AND deleted_at IS NULL AND status != 'cancelled') AS unpaid_total,
            (SELECT COUNT(1) FROM payments WHERE company_id = ? AND deleted_at IS NULL AND status = 'completed') AS payment_count,
            (SELECT COUNT(1) FROM service_requests WHERE company_id = ? AND deleted_at IS NULL AND status = 'open') AS open_service_requests,
            (SELECT COUNT(1) FROM work_orders WHERE company_id = ? AND deleted_at IS NULL AND status = 'in_progress') AS active_work_orders,
            (SELECT COUNT(1) FROM common_area_reservations WHERE company_id = ? AND deleted_at IS NULL AND status IN ('pending','approved') AND start_at >= NOW()) AS upcoming_reservations,
            (SELECT COUNT(1) FROM resident_profiles WHERE company_id = ? AND deleted_at IS NULL) AS resident_count,
            (SELECT COUNT(1) FROM unit_occupancies WHERE company_id = ? AND deleted_at IS NULL AND status = 'active') AS active_occupancy_count,
            (SELECT COUNT(1) FROM units WHERE company_id = ? AND deleted_at IS NULL) AS unit_count";

        $params = [
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
            $companyId,
        ];

        $result = $this->connection()->query($sql, $params);
        $row = $result->getRowArray();

        return is_array($row) ? $row : [];
    }

    private function connection(): BaseConnection
    {
        return $this->db ?? Database::connect();
    }

    protected function resolveCompanyId(): int
    {
        $runtimeCompanyId = RequestRuntime::getCompanyId();
        if ($runtimeCompanyId > 0) {
            return $runtimeCompanyId;
        }

        return (int) (service('request')->company_id ?? 0);
    }

    /**
     * @return array{
     *   finance: array{due_total:float,paid_total:float,unpaid_total:float,payment_count:int},
     *   operations: array{open_service_requests:int,active_work_orders:int,upcoming_reservations:int},
     *   residents: array{resident_count:int,active_occupancy_count:int,unit_count:int}
     * }
     */
    private function emptySummary(): array
    {
        return [
            'finance' => [
                'due_total' => 0.0,
                'paid_total' => 0.0,
                'unpaid_total' => 0.0,
                'payment_count' => 0,
            ],
            'operations' => [
                'open_service_requests' => 0,
                'active_work_orders' => 0,
                'upcoming_reservations' => 0,
            ],
            'residents' => [
                'resident_count' => 0,
                'active_occupancy_count' => 0,
                'unit_count' => 0,
            ],
        ];
    }
}
