<?php

namespace App\Services\Operation;

use App\Support\RequestRuntime;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class OperationsSummaryService
{
    public function __construct(
        private readonly ?BaseConnection $db = null
    ) {
    }

    /**
     * @return array{
     *   service_requests: array{open:int},
     *   work_orders: array{in_progress:int},
     *   reservations: array{pending:int,approved:int},
     *   maintenance: array{active_plans:int}
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
            'service_requests' => [
                'open' => (int) ($row['service_requests_open'] ?? 0),
            ],
            'work_orders' => [
                'in_progress' => (int) ($row['work_orders_in_progress'] ?? 0),
            ],
            'reservations' => [
                'pending' => (int) ($row['reservations_pending'] ?? 0),
                'approved' => (int) ($row['reservations_approved'] ?? 0),
            ],
            'maintenance' => [
                'active_plans' => (int) ($row['maintenance_active_plans'] ?? 0),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function fetchSummaryRow(int $companyId): array
    {
        $sql = "SELECT
            (SELECT COUNT(1) FROM service_requests WHERE company_id = ? AND deleted_at IS NULL AND status = 'open') AS service_requests_open,
            (SELECT COUNT(1) FROM work_orders WHERE company_id = ? AND deleted_at IS NULL AND status = 'in_progress') AS work_orders_in_progress,
            (SELECT COUNT(1) FROM common_area_reservations WHERE company_id = ? AND deleted_at IS NULL AND status = 'pending') AS reservations_pending,
            (SELECT COUNT(1) FROM common_area_reservations WHERE company_id = ? AND deleted_at IS NULL AND status = 'approved') AS reservations_approved,
            (SELECT COUNT(1) FROM asset_maintenance_plans WHERE company_id = ? AND deleted_at IS NULL AND status = 'active') AS maintenance_active_plans";

        $result = $this->connection()->query($sql, [$companyId, $companyId, $companyId, $companyId, $companyId]);
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
     *   service_requests: array{open:int},
     *   work_orders: array{in_progress:int},
     *   reservations: array{pending:int,approved:int},
     *   maintenance: array{active_plans:int}
     * }
     */
    private function emptySummary(): array
    {
        return [
            'service_requests' => ['open' => 0],
            'work_orders' => ['in_progress' => 0],
            'reservations' => ['pending' => 0, 'approved' => 0],
            'maintenance' => ['active_plans' => 0],
        ];
    }
}

