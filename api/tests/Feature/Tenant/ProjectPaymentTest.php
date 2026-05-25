<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class ProjectPaymentTest extends TenantTestCase
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

    private function createAcceptedQuote(): Quote
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'title' => 'Sitio Web Cali',
                'lines' => [
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
                ],
            ],
            $this->authHeader(),
        );

        $id = $response->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/send",
            [],
            $this->authHeader(),
        )->assertOk();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/accept",
            [],
            $this->authHeader(),
        )->assertOk();

        return self::$sharedTenant->run(function () use ($id) {
            return Quote::query()->find($id);
        });
    }

    #[Test]
    public function convert_quote_to_project(): void
    {
        $quote = $this->createAcceptedQuote();

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('quote_id', $quote->id)
            ->assertJsonPath('agreed_total_cents', 3400_00)
            ->assertJsonPath('paid_total_cents', 0)
            ->assertJsonPath('balance_due_cents', 3400_00)
            ->assertJsonPath('is_fully_paid', false)
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('quote_number', $quote->number)
            ->assertJsonPath('client_name', 'Acme Corp');

        $this->assertTrue(
            self::$sharedTenant->run(function () use ($quote) {
                return Quote::query()->find($quote->id)->status === 'converted';
            }),
        );
    }

    #[Test]
    public function convert_quote_to_project_is_idempotent(): void
    {
        $quote = $this->createAcceptedQuote();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        )->assertCreated();

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $response->assertStatus(409);
    }

    #[Test]
    public function project_copies_client_and_quote_snapshot(): void
    {
        $quote = $this->createAcceptedQuote();

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('quote_id', $quote->id)
            ->assertJsonPath('quote_number', $quote->number)
            ->assertJsonPath('client_name', 'Acme Corp')
            ->assertJsonPath('client_email', 'contact@acme.test')
            ->assertJsonPath('client_tax_id', '900123456-7');
    }

    #[Test]
    public function partial_payment_reduces_balance(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('project.paid_total_cents', 1000_00)
            ->assertJsonPath('project.balance_due_cents', 2400_00)
            ->assertJsonPath('project.is_fully_paid', false)
            ->assertJsonPath('payment.amount_cents', 1000_00)
            ->assertJsonPath('payment.kind', 'partial');
    }

    #[Test]
    public function multiple_partial_payments_until_zero(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 2000_00],
            $this->authHeader(),
        )->assertCreated();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1400_00],
            $this->authHeader(),
        )->assertCreated();

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('paid_total_cents', 3400_00)
            ->assertJsonPath('balance_due_cents', 0)
            ->assertJsonPath('is_fully_paid', true);
    }

    #[Test]
    public function mark_paid_without_partial_payments(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('project.paid_total_cents', 3400_00)
            ->assertJsonPath('project.balance_due_cents', 0)
            ->assertJsonPath('project.is_fully_paid', true)
            ->assertJsonPath('payment.kind', 'closure')
            ->assertJsonPath('payment.amount_cents', 3400_00);
    }

    #[Test]
    public function mark_paid_with_partial_payments_only_books_remaining(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00],
            $this->authHeader(),
        )->assertCreated();

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('project.paid_total_cents', 3400_00)
            ->assertJsonPath('project.balance_due_cents', 0)
            ->assertJsonPath('payment.kind', 'closure')
            ->assertJsonPath('payment.amount_cents', 2400_00);
    }

    #[Test]
    public function mark_paid_on_already_paid_is_idempotent(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $first = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $first->assertOk();

        $second = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $second->assertOk()
            ->assertJsonPath('project.is_fully_paid', true)
            ->assertJsonPath('project.balance_due_cents', 0);
    }

    #[Test]
    public function payments_history_returns_all_payments(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00],
            $this->authHeader(),
        )->assertCreated();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 500_00],
            $this->authHeader(),
        )->assertCreated();

        $response = $this->getJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function cannot_convert_non_accepted_quote(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
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

        $id = $create->json('id');

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $response->assertStatus(409);
    }

    #[Test]
    public function payment_exceeding_balance_is_rejected(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 9999_99],
            $this->authHeader(),
        );

        $response->assertStatus(422);
    }

    #[Test]
    public function create_project_manually(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/projects',
            [
                'client_id' => $client->id,
                'name' => 'Proyecto Manual',
                'type' => 'fixed',
                'started_at' => '2026-05-01',
            ],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('name', 'Proyecto Manual')
            ->assertJsonPath('type', 'fixed')
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('client_name', 'Acme Corp')
            ->assertJsonPath('started_at', '2026-05-01')
            ->assertJsonPath('is_fully_paid', true)
            ->assertJsonPath('balance_due_cents', 0)
            ->assertJsonPath('paid_total_cents', 0);
    }

    #[Test]
    public function manual_project_is_fully_paid_when_balance_is_zero(): void
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/projects',
            [
                'client_id' => $client->id,
                'name' => 'Proyecto sin monto',
                'type' => 'freelance',
            ],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('is_fully_paid', true)
            ->assertJsonPath('balance_due_cents', 0);

        $projectId = $response->json('id');

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('is_fully_paid', true);
    }

    #[Test]
    public function mark_paid_on_manual_project_with_no_balance_is_idempotent(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/projects',
            [
                'client_id' => $client->id,
                'name' => 'Manual zero',
                'type' => 'freelance',
            ],
            $this->authHeader(),
        );

        $projectId = $create->json('id');

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('project.is_fully_paid', true)
            ->assertJsonPath('project.balance_due_cents', 0)
            ->assertJsonPath('payment', null);

        $second = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $second->assertOk()
            ->assertJsonPath('project.is_fully_paid', true)
            ->assertJsonPath('payment', null);
    }

    #[Test]
    public function mark_paid_with_zero_balance_and_existing_payments_does_not_create_zero_closure(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $firstPayment = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 3400_00],
            $this->authHeader(),
        );

        $firstPayment->assertCreated()
            ->assertJsonPath('payment.kind', 'partial')
            ->assertJsonPath('payment.amount_cents', 3400_00);

        self::$sharedTenant->run(function () use ($projectId): void {
            Project::query()->whereKey($projectId)->update(['is_fully_paid' => false]);
        });

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            [],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('project.is_fully_paid', true)
            ->assertJsonPath('project.balance_due_cents', 0)
            ->assertJsonPath('payment.kind', 'partial')
            ->assertJsonPath('payment.amount_cents', 3400_00);

        $paymentsCount = self::$sharedTenant->run(function () use ($projectId): int {
            return \App\Models\ProjectPayment::query()->where('project_id', $projectId)->count();
        });

        $this->assertSame(1, $paymentsCount, 'No debe crearse un pago closure de monto 0.');
    }

    #[Test]
    public function list_projects_with_pagination(): void
    {
        $quote = $this->createAcceptedQuote();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        )->assertCreated();

        $response = $this->getJson(
            self::TENANT_BASE . '/api/v1/projects',
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function payments_do_not_create_finance_entries(): void
    {
        $quote = $this->createAcceptedQuote();

        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $projectId = $convert->json('id');

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00],
            $this->authHeader(),
        )->assertCreated();

        $financeEntriesExist = self::$sharedTenant->run(function () {
            return \Illuminate\Support\Facades\Schema::hasTable('finance_entries');
        });

        $this->assertFalse(
            $financeEntriesExist,
            'finance_entries table should not exist in Fase 6',
        );
    }
}
