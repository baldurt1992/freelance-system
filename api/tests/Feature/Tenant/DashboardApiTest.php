<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Client;
use App\Models\FinanceEntry;
use App\Models\Project;
use App\Models\ProjectPayment;
use App\Models\Quote;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class DashboardApiTest extends TenantTestCase
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
    public function dashboard_requires_auth(): void
    {
        $this->getJson(self::TENANT_BASE . '/api/v1/dashboard?month=2026-05')
            ->assertUnauthorized();
    }

    #[Test]
    public function dashboard_returns_operational_summary_for_selected_month(): void
    {
        self::$sharedTenant->run(function (): void {
            $client = Client::query()->create([
                'name' => 'Acme Corp',
                'email' => 'contact@acme.test',
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
            ]);

            $draftQuote = Quote::query()->create([
                'client_id' => $client->id,
                'number' => 'Q-2026-001',
                'status' => 'draft',
                'title' => 'Landing page',
                'notes' => null,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'subtotal_cents' => 500_000,
                'tax_cents' => 0,
                'total_cents' => 500_000,
                'tax_rate' => 0,
                'valid_until' => '2026-05-31',
            ]);
            $draftQuote->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-04 09:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-04 09:00:00'),
            ])->saveQuietly();

            $sentQuote = Quote::query()->create([
                'client_id' => $client->id,
                'number' => 'Q-2026-002',
                'status' => 'sent',
                'title' => 'Admin panel',
                'notes' => null,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'subtotal_cents' => 800_000,
                'tax_cents' => 0,
                'total_cents' => 800_000,
                'tax_rate' => 0,
                'valid_until' => '2026-05-31',
            ]);
            $sentQuote->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-08 11:30:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-08 11:30:00'),
            ])->saveQuietly();

            $activeProject = Project::query()->create([
                'client_id' => $client->id,
                'quote_id' => null,
                'name' => 'Website redesign',
                'notes' => null,
                'type' => 'freelance',
                'status' => 'active',
                'quote_number' => 'Q-2026-002',
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'agreed_total_cents' => 1_500_000,
                'paid_total_cents' => 400_000,
                'balance_due_cents' => 1_100_000,
                'is_fully_paid' => false,
                'started_at' => '2026-05-01',
                'completed_at' => null,
            ]);
            $activeProject->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-10 10:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-10 10:00:00'),
            ])->saveQuietly();

            $activePayment = ProjectPayment::query()->create([
                'project_id' => $activeProject->id,
                'amount_cents' => 400_000,
                'kind' => 'partial',
                'paid_at' => '2026-05-12',
            ]);
            $activePayment->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-12 12:00:00'),
            ])->saveQuietly();

            $completedProject = Project::query()->create([
                'client_id' => $client->id,
                'quote_id' => null,
                'name' => 'Legacy maintenance',
                'notes' => null,
                'type' => 'fixed',
                'status' => 'completed',
                'quote_number' => null,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'agreed_total_cents' => 700_000,
                'paid_total_cents' => 700_000,
                'balance_due_cents' => 0,
                'is_fully_paid' => true,
                'started_at' => '2026-04-01',
                'completed_at' => '2026-05-14',
            ]);
            $completedProject->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-14 16:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-14 16:00:00'),
            ])->saveQuietly();

            $completedPayment = ProjectPayment::query()->create([
                'project_id' => $completedProject->id,
                'amount_cents' => 700_000,
                'kind' => 'closure',
                'paid_at' => '2026-05-14',
            ]);
            $completedPayment->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-14 16:10:00'),
            ])->saveQuietly();

            $futureProject = Project::query()->create([
                'client_id' => $client->id,
                'quote_id' => null,
                'name' => 'Future project',
                'notes' => null,
                'type' => 'retainer',
                'status' => 'active',
                'quote_number' => null,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'agreed_total_cents' => 900_000,
                'paid_total_cents' => 0,
                'balance_due_cents' => 900_000,
                'is_fully_paid' => false,
                'started_at' => '2026-06-02',
                'completed_at' => null,
            ]);
            $futureProject->forceFill([
                'created_at' => CarbonImmutable::parse('2026-06-02 10:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-06-02 10:00:00'),
            ])->saveQuietly();

            $income = FinanceEntry::query()->create([
                'type' => 'income',
                'amount_cents' => 400_000,
                'occurred_on' => '2026-05-12',
                'description' => 'Primer abono proyecto',
                'category' => 'project_payment',
                'source_type' => 'project_payment',
                'source_id' => 101,
                'is_manual' => false,
            ]);
            $income->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-12 12:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-12 12:00:00'),
            ])->saveQuietly();

            $expense = FinanceEntry::query()->create([
                'type' => 'expense',
                'amount_cents' => 120_000,
                'occurred_on' => '2026-05-18',
                'description' => 'Suscripción hosting',
                'category' => 'subscriptions',
                'source_type' => 'manual',
                'source_id' => null,
                'is_manual' => true,
            ]);
            $expense->forceFill([
                'created_at' => CarbonImmutable::parse('2026-05-18 08:45:00'),
                'updated_at' => CarbonImmutable::parse('2026-05-18 08:45:00'),
            ])->saveQuietly();

            $oldIncome = FinanceEntry::query()->create([
                'type' => 'income',
                'amount_cents' => 999_000,
                'occurred_on' => '2026-04-20',
                'description' => 'Ingreso previo',
                'category' => 'manual',
                'source_type' => 'manual',
                'source_id' => null,
                'is_manual' => true,
            ]);
            $oldIncome->forceFill([
                'created_at' => CarbonImmutable::parse('2026-04-20 08:00:00'),
                'updated_at' => CarbonImmutable::parse('2026-04-20 08:00:00'),
            ])->saveQuietly();
        });

        $response = $this->getJson(
            self::TENANT_BASE . '/api/v1/dashboard?month=2026-05',
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('month', '2026-05')
            ->assertJsonPath('kpis.receivables_cents', 1_100_000)
            ->assertJsonPath('kpis.income_cents', 400_000)
            ->assertJsonPath('kpis.expense_cents', 120_000)
            ->assertJsonPath('kpis.pending_quotes_count', 2)
            ->assertJsonPath('kpis.active_projects_count', 1)
            ->assertJsonPath('pending.projects_with_balance_count', 1)
            ->assertJsonPath('pending.sent_quotes_count', 1)
            ->assertJsonPath('pending.draft_quotes_count', 1)
            ->assertJsonPath('financial_summary.net_cents', 280_000)
            ->assertJsonPath('financial_summary.label', 'surplus')
            ->assertJsonPath('recent_activity.0.kind', 'finance_entry')
            ->assertJsonPath('recent_activity.0.to', '/finances');

        $juneResponse = $this->getJson(
            self::TENANT_BASE . '/api/v1/dashboard?month=2026-06',
            $this->authHeader(),
        );

        $juneResponse->assertOk()
            ->assertJsonPath('kpis.receivables_cents', 2_000_000);
    }

    #[Test]
    public function dashboard_returns_zeroed_month_when_no_entries_exist_for_selected_month(): void
    {
        $response = $this->getJson(
            self::TENANT_BASE . '/api/v1/dashboard?month=2026-07',
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('month', '2026-07')
            ->assertJsonPath('kpis.income_cents', 0)
            ->assertJsonPath('kpis.expense_cents', 0)
            ->assertJsonPath('financial_summary.net_cents', 0)
            ->assertJsonPath('financial_summary.label', 'balanced')
            ->assertJsonCount(0, 'recent_activity');
    }
}
