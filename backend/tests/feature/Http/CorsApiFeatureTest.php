<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use Tests\Support\FeatureDatabaseTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class CorsApiFeatureTest extends FeatureDatabaseTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    public function testOptionsSitesPreflightFromViteOriginEmitsCorsHeaders(): void
    {
        $result = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization,content-type,idempotency-key',
        ])->options('/api/v1/sites');

        $result->assertStatus(204);
        $result->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $result->assertHeader('Access-Control-Allow-Methods');
        $result->assertHeader('Access-Control-Allow-Headers');
    }

    public function testUnauthorizedOriginDoesNotEchoWildcardEvenWhenMisconfigured(): void
    {
        $unknownOrigin = 'https://evil.example';
        $result = $this->withHeaders([
            'Origin' => $unknownOrigin,
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/v1/sites');

        $result->assertStatus(204);
        $originHeader = $result->response()->getHeaderLine('Access-Control-Allow-Origin');
        $this->assertNotSame('*', $originHeader);
        $this->assertNotSame($unknownOrigin, $originHeader);
    }
}
