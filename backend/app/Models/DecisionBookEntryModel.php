<?php
namespace App\Models;
use App\Core\TenantAwareModel;
class DecisionBookEntryModel extends TenantAwareModel
{
    protected $table='decision_book_entries'; protected $primaryKey='id'; protected $useSoftDeletes=true; protected $useTimestamps=true;
    protected $allowedFields=['company_id','meeting_id','decision_no','decision_date','subject','decision_text','vote_result','status','locked_at','document_id','created_by','updated_by'];
}
