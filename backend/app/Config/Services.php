<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Database\MigrationRunner;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Connect MigrationRunner to {@see Database::$tests} when {@see getenv()} `SPARK_USE_TESTS_DB` is `1`
     * (used by `scripts/test-reset-db.php` so CLI migrations hit the dedicated test database).
     */
    public static function migrations(?Migrations $config = null, ConnectionInterface|string|null $db = null, bool $getShared = true): MigrationRunner
    {
        if ($db === null && getenv('SPARK_USE_TESTS_DB') === '1') {
            $db = 'tests';
        }

        if ($getShared) {
            return static::getSharedInstance('migrations', $config, $db);
        }

        $config ??= config('Migrations');

        return new MigrationRunner($config, $db);
    }

    /*
     * public static function example($getShared = true)
     * {
     *     if ($getShared) {
     *         return static::getSharedInstance('example');
     *     }
     *
     *     return new \CodeIgniter\Example();
     * }
     */
}
