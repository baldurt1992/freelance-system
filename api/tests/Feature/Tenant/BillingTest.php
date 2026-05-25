<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Jobs\SendBillingDocumentEmail;
use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\FinanceEntry;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class BillingTest extends TenantTestCase
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
        static $counter = 0;
        $counter++;

        return self::$sharedTenant->run(function () use ($overrides, $counter) {
            return Client::query()->create([
                'name' => 'Acme Corp',
                'email' => "billing-{$counter}@acme.test",
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
                ...$overrides,
            ]);
        });
    }

    private function createActiveProject(): Project
    {
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/projects',
            [
                'client_id' => $client->id,
                'name' => 'Sitio Web Cali',
                'notes' => 'Entrega final del proyecto.',
                'type' => 'freelance',
                'agreed_total_cents' => 3400_00,
                'started_at' => '2026-05-01',
            ],
            $this->authHeader(),
        );

        $projectId = $response->json('id');

        return self::$sharedTenant->run(function () use ($projectId) {
            return Project::query()->find($projectId);
        });
    }

    #[Test]
    public function complete_project_creates_billing_document(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('project.status', 'completed')
            ->assertJsonPath('project.completed_at', now()->toDateString())
            ->assertJsonPath('billing_document.project_id', $project->id)
            ->assertJsonPath('billing_document.status', 'issued')
            ->assertJsonPath('billing_document.project_name', 'Sitio Web Cali')
            ->assertJsonPath('billing_document.client_name', 'Acme Corp')
            ->assertJsonPath('billing_document.agreed_total_cents', 3400_00)
            ->assertJsonPath('billing_document.paid_total_cents', 0)
            ->assertJsonPath('billing_document.balance_due_cents', 3400_00)
            ->assertJsonStructure(['billing_document' => ['number', 'issued_at', 'pdf_path']]);

        Queue::assertPushed(SendBillingDocumentEmail::class);

        $billingCount = self::$sharedTenant->run(function () use ($project) {
            return BillingDocument::query()->where('project_id', $project->id)->count();
        });

        $this->assertSame(1, $billingCount);
    }

    #[Test]
    public function complete_project_does_not_create_finance_entry(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $beforeCount = self::$sharedTenant->run(function () {
            return FinanceEntry::query()->count();
        });

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        )->assertOk();

        $afterCount = self::$sharedTenant->run(function () {
            return FinanceEntry::query()->count();
        });

        $this->assertSame($beforeCount, $afterCount, 'Complete must not create finance income');
    }

    #[Test]
    public function complete_project_is_idempotent(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $first = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $first->assertOk();
        $firstNumber = $first->json('billing_document.number');

        $second = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $second->assertOk()
            ->assertJsonPath('billing_document.number', $firstNumber);

        $billingCount = self::$sharedTenant->run(function () use ($project) {
            return BillingDocument::query()->where('project_id', $project->id)->count();
        });

        $this->assertSame(1, $billingCount);
        Queue::assertPushed(SendBillingDocumentEmail::class, 1);
    }

    #[Test]
    public function billing_pdf_endpoint_returns_200(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $complete = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $billingId = $complete->json('billing_document.id');

        $response = $this->get(
            self::TENANT_BASE . "/api/v1/billing-documents/{$billingId}/pdf",
            $this->authHeader(),
        );

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    #[Test]
    public function billing_pdf_uses_persisted_snapshot(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $complete = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $billingId = $complete->json('billing_document.id');

        self::$sharedTenant->run(function () use ($project): void {
            Client::query()->whereKey($project->client_id)->update(['name' => 'Nombre Cambiado S.A.S.']);
            Project::query()->whereKey($project->id)->update(['name' => 'Proyecto Renombrado']);
        });

        $snapshot = self::$sharedTenant->run(function () use ($billingId) {
            return BillingDocument::query()->find($billingId);
        });

        $this->assertSame('Acme Corp', $snapshot->client_name);
        $this->assertSame('Sitio Web Cali', $snapshot->project_name);

        $html = self::$sharedTenant->run(function () use ($snapshot) {
            return view('billing.pdf', ['document' => $snapshot])->render();
        });

        $this->assertStringContainsString('Acme Corp', $html);
        $this->assertStringContainsString('Sitio Web Cali', $html);
        $this->assertStringNotContainsString('Nombre Cambiado S.A.S.', $html);
        $this->assertStringNotContainsString('Proyecto Renombrado', $html);
    }

    #[Test]
    public function email_job_marks_billing_as_sent(): void
    {
        Mail::fake();

        $project = $this->createActiveProject();

        $complete = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $billingId = $complete->json('billing_document.id');

        $job = new SendBillingDocumentEmail($billingId);
        self::$sharedTenant->run(fn () => $job->handle());

        $status = self::$sharedTenant->run(function () use ($billingId) {
            return BillingDocument::query()->find($billingId)?->status;
        });

        $this->assertSame('sent', $status);
    }

    #[Test]
    public function list_billing_documents_by_project(): void
    {
        Queue::fake();

        $project = $this->createActiveProject();

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        )->assertOk();

        $response = $this->getJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/billing-documents",
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.project_id', $project->id);
    }

    #[Test]
    public function cannot_complete_non_active_project(): void
    {
        $project = $this->createActiveProject();

        self::$sharedTenant->run(function () use ($project): void {
            Project::query()->whereKey($project->id)->update(['status' => 'cancelled']);
        });

        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $response->assertStatus(409);
    }

    #[Test]
    public function billing_numbers_are_unique_per_tenant(): void
    {
        Queue::fake();

        $projectA = $this->createActiveProject();
        $projectB = $this->createActiveProject();

        $first = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectA->id}/complete",
            [],
            $this->authHeader(),
        );

        $second = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$projectB->id}/complete",
            [],
            $this->authHeader(),
        );

        $numberA = $first->json('billing_document.number');
        $numberB = $second->json('billing_document.number');

        $this->assertNotSame($numberA, $numberB);
        $this->assertStringStartsWith('CC-', $numberA);
    }
}
