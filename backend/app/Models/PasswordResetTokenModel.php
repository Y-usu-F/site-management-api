<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetTokenModel extends Model
{
    protected $table = 'password_reset_tokens';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    protected $allowedFields = [
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
        'requested_ip',
        'requested_user_agent',
        'created_by',
        'updated_by',
    ];
}
