<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class TenantAuthTest extends TenantTestCase
{
    private const TENANT_BASE = 'http://test.localhost';

    #[Test]
    public function tenant_auth_flow_works(): void
    {
        $this->getJson(self::TENANT_BASE . '/api/v1/auth/me')
            ->assertUnauthorized();

        $login = $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'secret-password',
        ]);

        $login
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email']]);

        $this->assertSame('Bearer', $login->json('token_type'));

        $token = $login->json('token');

        $this->getJson(self::TENANT_BASE . '/api/v1/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ])
            ->assertOk()
            ->assertJsonPath('tenant.id', 'test')
            ->assertJsonPath('tenant.tax_enabled', false)
            ->assertJsonPath('tenant.currency', 'COP');
    }

    #[Test]
    public function authenticated_user_can_update_password(): void
    {
        $login = $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'secret-password',
        ])->assertOk();

        $token = $login->json('token');

        $this->patchJson(
            self::TENANT_BASE . '/api/v1/auth/password',
            [
                'current_password' => 'secret-password',
                'password' => 'new-secret-password',
            ],
            [
                'Authorization' => 'Bearer ' . $token,
            ],
        )
            ->assertOk()
            ->assertJsonPath('message', 'Contraseña actualizada.');

        $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'secret-password',
        ])->assertStatus(422);

        $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'new-secret-password',
        ])->assertOk();
    }
}
