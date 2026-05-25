<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\FinanceEntry;
use App\Models\Quote;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class FinanceEntryTest extends TenantTestCase
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

    private function createClient(): Client
    {
        return self::$sharedTenant->run(function () {
            return Client::query()->create([
                'name' => 'Acme Corp',
                'email' => 'contact@acme.test',
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
            ]);
        });
    }

    private function createAcceptedQuote(): Quote
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            [
                'client_id' => $client->id,
                'title' => 'Proyecto Finance',
                'lines' => [
                    [
                        'description' => 'Servicio',
                        'quantity' => 1,
                        'unit_amount_cents' => 3400_00,
                        'sort_order' => 0,
                    ],
                ],
            ],
            $this->authHeader(),
        );

        $id = $create->json('id');

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
            return Quote::query()->findOrFail($id);
        });
    }

    private function convertAcceptedQuoteToProjectId(Quote $quote): int
    {
        $convert = $this->postJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quote->id}/convert-to-project",
            [],
            $this->authHeader(),
        );

        $convert->assertCreated();

        return (int) $convert->json('id');
    }

    #[Test]
    public function partial_payment_creates_one_income_entry(): void
    {
        $projectId = $this->convertAcceptedQuoteToProjectId($this->createAcceptedQuote());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00, 'paid_at' => '2026-05-25'],
            $this->authHeader(),
        )->assertCreated();

        $summary = $this->getJson(
            self::TENANT_BASE . '/api/v1/finances/summary?month=2026-05',
            $this->authHeader(),
        );

        $summary->assertOk()
            ->assertJsonPath('total_income_cents', 1000_00)
            ->assertJsonPath('total_expense_cents', 0)
            ->assertJsonPath('net_cents', 1000_00)
            ->assertJsonPath('label', 'surplus');
    }

    #[Test]
    public function mark_paid_without_partials_creates_one_income_for_total(): void
    {
        $projectId = $this->convertAcceptedQuoteToProjectId($this->createAcceptedQuote());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            ['paid_at' => '2026-05-26'],
            $this->authHeader(),
        )->assertOk();

        $entries = $this->getJson(
            self::TENANT_BASE . '/api/v1/finances/entries?month=2026-05&type=income',
            $this->authHeader(),
        );

        $entries->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.amount_cents', 3400_00)
            ->assertJsonPath('data.0.source_type', 'project_payment')
            ->assertJsonPath('data.0.occurred_on', '2026-05-26');
    }

    #[Test]
    public function mark_paid_with_partials_only_books_remaining_income(): void
    {
        $projectId = $this->convertAcceptedQuoteToProjectId($this->createAcceptedQuote());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00, 'paid_at' => '2026-05-20'],
            $this->authHeader(),
        )->assertCreated();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/mark-paid",
            ['paid_at' => '2026-05-21'],
            $this->authHeader(),
        )->assertOk();

        $summary = $this->getJson(
            self::TENANT_BASE . '/api/v1/finances/summary?month=2026-05',
            $this->authHeader(),
        );

        $summary->assertOk()
            ->assertJsonPath('total_income_cents', 3400_00)
            ->assertJsonPath('net_cents', 3400_00);
    }

    #[Test]
    public function manual_expense_and_income_affect_monthly_summary(): void
    {
        $this->postJson(
            self::TENANT_BASE . '/api/v1/finances/entries',
            [
                'type' => 'income',
                'amount_cents' => 500_00,
                'occurred_on' => '2026-05-10',
                'description' => 'Rifa',
                'category' => 'prize',
            ],
            $this->authHeader(),
        )->assertCreated();

        $this->postJson(
            self::TENANT_BASE . '/api/v1/finances/entries',
            [
                'type' => 'expense',
                'amount_cents' => 200_00,
                'occurred_on' => '2026-05-11',
                'description' => 'AI tooling',
                'category' => 'ai_tools',
            ],
            $this->authHeader(),
        )->assertCreated();

        $summary = $this->getJson(
            self::TENANT_BASE . '/api/v1/finances/summary?month=2026-05',
            $this->authHeader(),
        );

        $summary->assertOk()
            ->assertJsonPath('total_income_cents', 500_00)
            ->assertJsonPath('total_expense_cents', 200_00)
            ->assertJsonPath('net_cents', 300_00)
            ->assertJsonPath('label', 'surplus');
    }

    #[Test]
    public function duplicate_project_payment_does_not_duplicate_finance_entry(): void
    {
        $projectId = $this->convertAcceptedQuoteToProjectId($this->createAcceptedQuote());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00, 'paid_at' => '2026-05-25'],
            $this->authHeader(),
        )->assertCreated();

        $count = self::$sharedTenant->run(function (): int {
            return FinanceEntry::query()->where('source_type', 'project_payment')->count();
        });

        $this->assertSame(1, $count);
    }

    #[Test]
    public function automatic_entries_cannot_be_updated_or_deleted(): void
    {
        $projectId = $this->convertAcceptedQuoteToProjectId($this->createAcceptedQuote());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectId}/payments",
            ['amount_cents' => 1000_00, 'paid_at' => '2026-05-25'],
            $this->authHeader(),
        )->assertCreated();

        $entryId = self::$sharedTenant->run(function (): int {
            return (int) FinanceEntry::query()
                ->where('source_type', 'project_payment')
                ->value('id');
        });

        $this->patchJson(
            self::TENANT_BASE . "/api/v1/finances/entries/{$entryId}",
            ['description' => 'Intento de edición'],
            $this->authHeader(),
        )->assertStatus(409);

        $this->deleteJson(
            self::TENANT_BASE . "/api/v1/finances/entries/{$entryId}",
            [],
            $this->authHeader(),
        )->assertStatus(409);
    }
}
