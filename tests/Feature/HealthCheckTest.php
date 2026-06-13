<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('healthcheck')]
class HealthCheckTest extends TestCase
{
    #[Test]
    public function health_check_returns_200_when_all_systems_are_healthy(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'environment',
                'version',
                'checks' => [
                    'database',
                    'cache',
                    'queue',
                    'storage',
                    'app',
                ],
            ])
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database.status', 'healthy')
            ->assertJsonPath('checks.cache.status', 'healthy');
    }
}
