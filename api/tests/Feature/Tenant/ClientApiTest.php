<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class ClientApiTest extends TenantTestCase
{
    private const TENANT_BASE = 'http://test.localhost';

    private function getToken(): string
    {
        $login = $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'secret-password',
        ]);

        return $login->json('token');
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getToken()];
    }

    #[Test]
    public function list_requires_auth(): void
    {
        $this->getJson(self::TENANT_BASE . '/api/v1/clients')
            ->assertUnauthorized();
    }

    #[Test]
    public function list_returns_paginated_clients_when_authenticated(): void
    {
        $this->postJson(
            self::TENANT_BASE . '/api/v1/clients',
            ['name' => 'List Test Client'],
            $this->authHeader(),
        )->assertCreated();

        $list = $this->getJson(
            self::TENANT_BASE . '/api/v1/clients',
            $this->authHeader(),
        );

        $list->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.current_page', 1);

        $this->assertNotEmpty($list->json('data'));
    }

    #[Test]
    public function create_and_show_client(): void
    {
        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/clients',
            [
                'name' => 'Acme Corp',
                'email' => 'contact@acme.test',
                'phone' => '+57 300 123 4567',
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
                'notes' => 'Cliente preferencial',
            ],
            $this->authHeader(),
        );

        $create->assertCreated()
            ->assertJsonPath('name', 'Acme Corp')
            ->assertJsonPath('email', 'contact@acme.test')
            ->assertJsonPath('id', fn($id) => is_int($id) && $id > 0);

        $id = $create->json('id');

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/clients/{$id}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('name', 'Acme Corp');
    }

    #[Test]
    public function update_client(): void
    {
        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/clients',
            ['name' => 'Original Name'],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $update = $this->putJson(
            self::TENANT_BASE . "/api/v1/clients/{$id}",
            ['name' => 'Updated Name'],
            $this->authHeader(),
        );

        $update->assertOk()
            ->assertJsonPath('name', 'Updated Name')
            ->assertJsonPath('id', $id);
    }

    #[Test]
    public function delete_client(): void
    {
        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/clients',
            ['name' => 'To Delete'],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $delete = $this->deleteJson(
            self::TENANT_BASE . "/api/v1/clients/{$id}",
            [],
            $this->authHeader(),
        );

        $delete->assertNoContent();

        $this->getJson(
            self::TENANT_BASE . "/api/v1/clients/{$id}",
            $this->authHeader(),
        )->assertNotFound();
    }

    #[Test]
    public function validation_422_on_invalid_email(): void
    {
        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/clients',
            [
                'name' => 'Test',
                'email' => 'not-an-email',
            ],
            $this->authHeader(),
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
