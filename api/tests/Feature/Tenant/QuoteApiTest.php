<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Quote;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class QuoteApiTest extends TenantTestCase
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

    private function createClient(array $overrides = []): Client
    {
        return self::$sharedTenant->run(function () use ($overrides) {
            return Client::query()->create([
                'name' => 'Acme Corp',
                'email' => 'contact@acme.test',
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
                ...$overrides,
            ]);
        });
    }

    private function validLines(): array
    {
        return [
            [
                'description' => 'Diseño UI',
                'quantity' => 2,
                'unit_amount_cents' => 500_00,
                'sort_order' => 0,
            ],
            [
                'description' => 'Desarrollo frontend',
                'quantity' => 3,
                'unit_amount_cents' => 800_00,
                'sort_order' => 1,
            ],
        ];
    }

    #[Test]
    public function list_requires_auth(): void
    {
        $this->getJson(self::TENANT_BASE . '/api/v1/quotes')
            ->assertUnauthorized();
    }

    #[Test]
    public function create_quote_with_lines_calculates_totals_correctly(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'title' => 'Proyecto Web',
                'lines' => $this->validLines(),
            ],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('status', 'draft')
            ->assertJsonPath('subtotal_cents', 3400_00)
            ->assertJsonPath('tax_cents', 0)
            ->assertJsonPath('total_cents', 3400_00)
            ->assertJsonPath('number', fn($n) => str_starts_with($n, 'Q-'))
            ->assertJsonPath('client_name', 'Acme Corp');

        $this->assertCount(2, $response->json('lines'));
    }

    #[Test]
    public function backend_recalculates_ignoring_frontend_totals(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'lines' => $this->validLines(),
                'subtotal_cents' => 999_99,
                'tax_cents' => 999_99,
                'total_cents' => 999_99,
            ],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('subtotal_cents', 3400_00)
            ->assertJsonPath('tax_cents', 0)
            ->assertJsonPath('total_cents', 3400_00);
    }

    #[Test]
    public function transition_draft_to_sent_to_accepted(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $sent = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/send",
            [],
            $this->authHeader(),
        );

        $sent->assertOk()
            ->assertJsonPath('status', 'sent')
            ->assertJsonPath('sent_at', fn($d) => $d !== null);

        $accepted = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/accept",
            [],
            $this->authHeader(),
        );

        $accepted->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('accepted_at', fn($d) => $d !== null);
    }

    #[Test]
    public function invalid_transition_returns_error(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/accept",
            [],
            $this->authHeader(),
        )->assertStatus(409);
    }

    #[Test]
    public function pdf_responds_with_pdf_content_type(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $response = $this->get(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/pdf",
            $this->authHeader(),
        );

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function tenant_without_tax_enabled_has_zero_tax(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'lines' => [
                    [
                        'description' => 'Servicio',
                        'quantity' => 1,
                        'unit_amount_cents' => 1000_00,
                        'sort_order' => 0,
                    ],
                ],
            ],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('tax_cents', 0)
            ->assertJsonPath('total_cents', 1000_00);
    }

    #[Test]
    public function client_snapshot_persists_after_client_changes(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $id = $create->json('id');

        self::$sharedTenant->run(function () use ($client) {
            $client->update(['name' => 'Nombre Cambiado']);
        });

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('client_name', 'Acme Corp');
    }

    #[Test]
    public function deleting_client_with_quotes_fails_due_to_restriction(): void
    {
        $client = $this->createClient();

        $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        )->assertCreated();

        $this->deleteJson(
            self::TENANT_BASE . "/api/v1/clients/{$client->id}",
            [],
            $this->authHeader(),
        )->assertStatus(409);
    }

    #[Test]
    public function quote_numbers_are_unique(): void
    {
        $client = $this->createClient();

        $first = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $second = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $firstNumber = $first->json('number');
        $secondNumber = $second->json('number');

        $this->assertNotEquals($firstNumber, $secondNumber);
        $this->assertTrue(
            (int) substr($secondNumber, 2) > (int) substr($firstNumber, 2),
            'El segundo número debe ser mayor que el primero',
        );
    }

    #[Test]
    public function deleting_draft_does_not_reuse_number(): void
    {
        $client = $this->createClient();

        $first = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $firstNumber = $first->json('number');
        $firstId = $first->json('id');

        $this->deleteJson(
            self::TENANT_BASE . "/api/v1/quotes/{$firstId}",
            [],
            $this->authHeader(),
        )->assertNoContent();

        $second = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $secondNumber = $second->json('number');

        $this->assertNotEquals($firstNumber, $secondNumber);
        $this->assertTrue(
            (int) substr($secondNumber, 2) > (int) substr($firstNumber, 2),
            'El número nuevo no debe reutilizarse tras borrar',
        );
    }

    #[Test]
    public function quote_lines_respect_sort_order(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'lines' => [
                    [
                        'description' => 'Zebra',
                        'quantity' => 1,
                        'unit_amount_cents' => 100_00,
                        'sort_order' => 2,
                    ],
                    [
                        'description' => 'Alpha',
                        'quantity' => 1,
                        'unit_amount_cents' => 100_00,
                        'sort_order' => 0,
                    ],
                    [
                        'description' => 'Beta',
                        'quantity' => 1,
                        'unit_amount_cents' => 100_00,
                        'sort_order' => 1,
                    ],
                ],
            ],
            $this->authHeader(),
        );

        $lines = $response->json('lines');
        $this->assertEquals('Alpha', $lines[0]['description']);
        $this->assertEquals('Beta', $lines[1]['description']);
        $this->assertEquals('Zebra', $lines[2]['description']);
    }

    #[Test]
    public function only_draft_can_be_deleted(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $id = $create->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/send",
            [],
            $this->authHeader(),
        )->assertOk();

        $this->deleteJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}",
            [],
            $this->authHeader(),
        )->assertStatus(409);
    }

    #[Test]
    public function show_returns_404_for_missing_quote(): void
    {
        $this->getJson(
            self::TENANT_BASE . '/api/v1/quotes/99999',
            $this->authHeader(),
        )->assertNotFound()
         ->assertJsonPath('message', 'Cotización no encontrada');
    }

    #[Test]
    public function list_can_filter_quotes_by_search(): void
    {
        $client = $this->createClient();

        $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'title' => 'Sitio Web Cali',
                'lines' => $this->validLines(),
            ],
            $this->authHeader(),
        )->assertCreated();

        $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'title' => 'Branding Medellín',
                'lines' => $this->validLines(),
            ],
            $this->authHeader(),
        )->assertCreated();

        $response = $this->getJson(
            self::TENANT_BASE . '/api/v1/quotes?search=Cali',
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Sitio Web Cali');
    }
}
