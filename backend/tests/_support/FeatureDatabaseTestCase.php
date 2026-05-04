<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Database\Seeds\RbacSeeder;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Base case for HTTP feature tests that rely on App migrations against the tests DB group.
 *
 * DatabaseTestTrait runs migrate regress when {@see CIUnitTestCase::$refresh} is true. Many
 * additive migrations intentionally use idempotent / no-op {@see \CodeIgniter\Database\Migration::down()}
 * paths for tests; full rollback then re-migrate leaves the physical schema and the rows in the
 * `migrations` table out of sync ("table already exists", duplicate indexes, missing tables).
 *
 * Use `composer test:reset-db` before a full suite (or CI) so the schema is migrated once; tests
 * only run `migrate latest` on set-up, which is a no-op when history is complete.
 */
abstract class FeatureDatabaseTestCase extends CIUnitTestCase
{
    /** @var list<string>|string|null */
    protected $namespace = 'App';

    protected $migrate = true;

    protected $seed = RbacSeeder::class;

    protected $refresh = false;

    protected function setUp(): void
    {
        parent::setUp();

        $request = service('request');
        if ($request instanceof IncomingRequest) {
            foreach ([
                'company_id',
                'user_id',
                'user',
                'roles',
                'permissions',
                'session_id',
                'rate_limit_key',
                'target_company_id',
            ] as $key) {
                if (isset($request->{$key})) {
                    unset($request->{$key});
                }
            }
        }
    }
}
