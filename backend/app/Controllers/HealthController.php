<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class HealthController extends Controller
{
    public function health()
    {
        return $this->response->setJSON(['status' => 'ok']);
    }

    public function ready()
    {
        return $this->response->setJSON(['status' => 'ready']);
    }
}
