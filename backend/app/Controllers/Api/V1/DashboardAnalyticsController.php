<?php

namespace App\Controllers\Api\V1;

use App\Core\ApiController;
use App\Services\Analytics\DashboardAnalyticsService;
use Throwable;

class DashboardAnalyticsController extends ApiController
{
    public function __construct(
        private readonly DashboardAnalyticsService $service = new DashboardAnalyticsService()
    ) {
    }

    public function show($id = null)
    {
        try {
            return $this->ok('Dashboard analytics ozeti getirildi', $this->service->summary());
        } catch (Throwable $e) {
            return $this->failFromException($e);
        }
    }
}
