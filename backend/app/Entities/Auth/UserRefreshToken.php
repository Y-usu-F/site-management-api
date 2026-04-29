<?php

namespace App\Entities\Auth;

use CodeIgniter\Entity\Entity;

class UserRefreshToken extends Entity
{
    protected $dates = [
        'expires_at',
        'issued_at',
        'last_used_at',
        'revoked_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
