<?php

namespace App\Services\Analytics;

use App\Support\RequestRuntime;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

class DashboardAnalyticsService
{
    private const TREND_DAYS = 30;

    public function __construct(
        private readonly ?BaseConnection $db = null
    ) {
    }

    /**
     * @return array{
     *   finance: array{due_total:float,paid_total:float,unpaid_total:float,payment_count:int},
     *   operations: array{open_service_requests:int,active_work_orders:int,upcoming_reservations:int},
     *   residents: array{resident_count:int,active_occupancy_count:int,unit_count:int},
     *   trends: array{
     *     payments_last_30_days:list<array{date:string,total:float}>,
     *     service_requests_last_30_days:list<array{date:string,count:int}>
     *   },
     *   distributions: array{
     *     service_request_statuses:list<array{status:string,count:int}>,
     *     work_order_statuses:list<array{status:string,count:int}>
     *   }
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
            'trends' => [
                'payments_last_30_days' => $this->buildPaymentsTrend($companyId),
                'service_requests_last_30_days' => $this->buildServiceRequestsTrend($companyId),
            ],
            'distributions' => [
                'service_request_statuses' => $this->fetchStatusDistribution($companyId, 'service_requests'),
                'work_order_statuses' => $this->fetchStatusDistribution($companyId, 'work_orders'),
            ],
        ];
    }
    /**
     * @return list<array{date:string,total:float}>
     */
    protected function buildPaymentsTrend(int $companyId): array
    {
        $sql = "SELECT DATE(payment_date) AS day, COALESCE(SUM(amount), 0) AS total
            FROM payments
            WHERE company_id = ?
              AND deleted_at IS NULL
              AND status = 'completed'
              AND DATE(payment_date) BETWEEN DATE_SUB(CURDATE(), INTERVAL ? DAY) AND CURDATE()
            GROUP BY DATE(payment_date)";
        $daysBack = self::TREND_DAYS - 1;
        $rows = $this->connection()->query($sql, [$companyId, $daysBack])->getResultArray();
        return $this->fillDailySeries($rows, 'total');
    }

    /**
     * @return list<array{date:string,count:int}>
     */
    protected function buildServiceRequestsTrend(int $companyId): array
    {
        $sql = "SELECT DATE(created_at) AS day, COUNT(1) AS count
            FROM service_requests
            WHERE company_id = ?
              AND deleted_at IS NULL
              AND DATE(created_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL ? DAY) AND CURDATE()
            GROUP BY DATE(created_at)";
        $daysBack = self::TREND_DAYS - 1;
        $rows = $this->connection()->query($sql, [$companyId, $daysBack])->getResultArray();
        return $this->fillDailySeries($rows, 'count');
    }

    /**
     * @return list<array{status:string,count:int}>
     */
    protected function fetchStatusDistribution(int $companyId, string $table): array
    {
        $allowedTables = ['service_requests', 'work_orders'];
        if (! in_array($table, $allowedTables, true)) {
            return [];
        }

        $sql = "SELECT status, COUNT(1) AS count
            FROM {$table}
            WHERE company_id = ?
              AND deleted_at IS NULL
            GROUP BY status
            ORDER BY status ASC";
        $rows = $this->connection()->query($sql, [$companyId])->getResultArray();

        return array_map(static fn (array $row): array => [
            'status' => (string) ($row['status'] ?? ''),
            'count' => (int) ($row['count'] ?? 0),
        ], $rows);
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array{date:string,total:float}|array{date:string,count:int}>
     */
    protected function fillDailySeries(array $rows, string $valueKey): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $day = (string) ($row['day'] ?? '');
            if ($day === '') {
                continue;
            }
            $indexed[$day] = $row[$valueKey] ?? 0;
        }

        $series = [];
        $today = new \DateTimeImmutable('today');
        $start = $today->sub(new \DateInterval('P' . (self::TREND_DAYS - 1) . 'D'));
        for ($cursor = $start; $cursor <= $today; $cursor = $cursor->add(new \DateInterval('P1D'))) {
            $date = $cursor->format('Y-m-d');
            if ($valueKey === 'total') {
                $series[] = [
                    'date' => $date,
                    'total' => (float) ($indexed[$date] ?? 0),
                ];
                continue;
            }

            $series[] = [
                'date' => $date,
                'count' => (int) ($indexed[$date] ?? 0),
            ];
        }

        return $series;
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
     *   residents: array{resident_count:int,active_occupancy_count:int,unit_count:int},
     *   trends: array{
     *     payments_last_30_days:list<array{date:string,total:float}>,
     *     service_requests_last_30_days:list<array{date:string,count:int}>
     *   },
     *   distributions: array{
     *     service_request_statuses:list<array{status:string,count:int}>,
     *     work_order_statuses:list<array{status:string,count:int}>
     *   }
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
            'trends' => [
                'payments_last_30_days' => $this->fillDailySeries([], 'total'),
                'service_requests_last_30_days' => $this->fillDailySeries([], 'count'),
            ],
            'distributions' => [
                'service_request_statuses' => [],
                'work_order_statuses' => [],
            ],
        ];
    }
}
