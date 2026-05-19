<?php

declare(strict_types=1);

namespace Tests\Feature\Central;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HealthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function central_health_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/central/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('context', 'central');
    }
}
