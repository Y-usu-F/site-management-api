<?php

namespace App\Models;

use App\Core\TenantAwareModel;

class CommonAreaReservationModel extends TenantAwareModel
{
    protected $table = 'common_area_reservations';
    protected $primaryKey = 'id';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = ['company_id', 'common_area_id', 'unit_id', 'resident_profile_id', 'reservation_no', 'start_at', 'end_at', 'participant_count', 'status', 'approved_by', 'approved_at', 'rejected_reason', 'cancelled_reason', 'notes', 'created_by', 'updated_by'];
}
