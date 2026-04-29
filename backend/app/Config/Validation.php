<?php

namespace Config;

use App\Validation\AuthValidation;
use App\Validation\ProfileValidation;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * @var list<string>
     */
    public array $ruleSets = [
        Rules::class,
        FormatRules::class,
        FileRules::class,
        CreditCardRules::class,
    ];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /**
     * Faz 04 AuthValidation hazirlik kurallari.
     *
     * Bu rule gruplari ilgili controller/service ticketlarinda
     * kullanima alinacaktir.
     *
     * @var array<string, array<string, string>>
     */
    public array $auth = [];

    /**
     * Faz 04 ProfileValidation hazirlik kurallari.
     *
     * @var array<string, array<string, string>>
     */
    public array $profile = [];

    public function __construct()
    {
        parent::__construct();

        $this->auth = [
            'login' => AuthValidation::loginRules(),
            'refresh' => AuthValidation::refreshRules(),
            'forgotPassword' => AuthValidation::forgotPasswordRules(),
            'resetPassword' => AuthValidation::resetPasswordRules(),
        ];

        $this->profile = [
            'update' => ProfileValidation::updateRules(),
            'changePassword' => ProfileValidation::changePasswordRules(),
        ];
    }
}
