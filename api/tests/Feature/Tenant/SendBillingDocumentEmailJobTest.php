<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Jobs\SendBillingDocumentEmail;
use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

/**
 * Boundary queue/tenant para SendBillingDocumentEmail.
 * Verifica que Stancl QueueTenancyBootstrapper restaure contexto tenant en el worker.
 */
final class SendBillingDocumentEmailJobTest extends TenantTestCase
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

    private function useCentralDatabaseQueue(): void
    {
        config([
            'queue.default' => 'database',
            // Cola central explícita: los jobs tenant llevan tenant_id en el payload (Stancl).
            'queue.connections.database.connection' => 'sqlite',
        ]);
    }

    private function createActiveProject(): Project
    {
        $client = self::$sharedTenant->run(function (): Client {
            static $counter = 0;
            $counter++;

            return Client::query()->create([
                'name' => 'Acme Corp',
                'email' => "billing-job-{$counter}@acme.test",
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
            ]);
        });

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

        return self::$sharedTenant->run(fn () => Project::query()->findOrFail($projectId));
    }

    private function completeProject(Project $project): int
    {
        $response = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $response->assertOk();

        return (int) $response->json('billing_document.id');
    }

    private function centralJobs()
    {
        return DB::connection('sqlite')->table('jobs');
    }

    private function clearQueuedJobs(): void
    {
        $this->centralJobs()->delete();
    }

    #[Test]
    public function billing_email_job_without_tenant_context_does_not_update_document(): void
    {
        Mail::fake();
        Queue::fake();

        $project = $this->createActiveProject();
        $billingId = $this->completeProject($project);

        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $this->assertFalse(tenancy()->initialized);

        $before = self::$sharedTenant->run(fn () => BillingDocument::query()->find($billingId));

        $this->assertSame('issued', $before?->status);
        $this->assertNull($before?->sent_at);

        try {
            (new SendBillingDocumentEmail($billingId))->handle();
        } catch (\Illuminate\Database\QueryException) {
            // Sin bootstrap tenant el worker consulta la BD central, que no tiene tablas de negocio.
        }

        $after = self::$sharedTenant->run(fn () => BillingDocument::query()->find($billingId));

        $this->assertSame('issued', $after?->status);
        $this->assertNull($after?->sent_at);
    }

    #[Test]
    public function billing_email_job_marks_document_sent_after_tenant_bootstrap(): void
    {
        Mail::fake();
        Queue::fake();

        $project = $this->createActiveProject();
        $billingId = $this->completeProject($project);

        self::$sharedTenant->run(function () use ($billingId): void {
            (new SendBillingDocumentEmail($billingId))->handle();
        });

        $document = self::$sharedTenant->run(fn () => BillingDocument::query()->find($billingId));

        $this->assertNotNull($document);
        $this->assertSame('sent', $document->status);
        $this->assertNotNull($document->sent_at);
    }

    #[Test]
    public function complete_project_dispatches_billing_email_after_commit_with_tenant_payload(): void
    {
        Mail::fake();
        $this->useCentralDatabaseQueue();
        $this->clearQueuedJobs();

        $project = $this->createActiveProject();

        $this->assertSame(0, $this->centralJobs()->count());

        $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        )->assertOk();

        $this->assertSame(1, $this->centralJobs()->count());

        $payload = json_decode((string) $this->centralJobs()->value('payload'), true);

        $this->assertSame(SendBillingDocumentEmail::class, $payload['displayName'] ?? null);
        $this->assertSame(self::$sharedTenant->getTenantKey(), $payload['tenant_id'] ?? null);
    }
}
