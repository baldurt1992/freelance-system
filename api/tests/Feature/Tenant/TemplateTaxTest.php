<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\BillingDocument;
use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\Project;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class TemplateTaxTest extends TenantTestCase
{
    private const TENANT_BASE = 'http://test.localhost';

    private ?string $cachedToken = null;

    private function getToken(): string
    {
        if ($this->cachedToken !== null) {
            return $this->cachedToken;
        }

        $login = $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => 'owner@test.localhost',
            'password' => 'secret-password',
        ]);

        $this->cachedToken = $login->json('token');

        return $this->cachedToken;
    }

    private function authHeader(): array
    {
        return ['Authorization' => 'Bearer ' . $this->getToken()];
    }

    private function authHeaderFor(string $email, string $password): array
    {
        $login = $this->postJson(self::TENANT_BASE . '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $login->assertOk();

        return ['Authorization' => 'Bearer ' . $login->json('token')];
    }

    private function createClient(array $overrides = []): Client
    {
        static $counter = 0;
        $counter++;

        return self::$sharedTenant->run(function () use ($overrides, $counter) {
            return Client::query()->create([
                'name' => 'Acme Corp',
                'email' => "client-{$counter}@acme.test",
                'tax_id' => '900123456-7',
                'address' => 'Calle 100 # 15-20',
                ...$overrides,
            ]);
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function validLines(): array
    {
        return [
            [
                'description' => 'Servicio profesional',
                'quantity' => 1,
                'unit_amount_cents' => 1_000_00,
                'sort_order' => 0,
            ],
        ];
    }

    private function setTaxEnabled(bool $enabled): void
    {
        $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => $enabled],
            $this->authHeader(),
        )->assertOk();
    }

    #[Test]
    public function tax_enabled_false_creates_quote_with_zero_tax(): void
    {
        $this->setTaxEnabled(false);
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('tax_cents', 0)
            ->assertJsonPath('total_cents', 1_000_00);
    }

    #[Test]
    public function tax_enabled_true_calculates_tax_with_money_math(): void
    {
        $this->setTaxEnabled(true);
        $client = $this->createClient();

        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $response->assertCreated()
            ->assertJsonPath('subtotal_cents', 1_000_00)
            ->assertJsonPath('tax_cents', 190_00)
            ->assertJsonPath('total_cents', 1_190_00);
    }

    #[Test]
    public function patch_settings_updates_tax_enabled_and_rate(): void
    {
        $this->setTaxEnabled(false);

        $response = $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => true],
            $this->authHeader(),
        );

        $response->assertOk()
            ->assertJsonPath('tax_enabled', true);

        $this->assertSame(19.0, (float) $response->json('tax_rate'));
    }

    #[Test]
    public function toggling_tax_recalculates_existing_draft_quote(): void
    {
        $this->setTaxEnabled(false);
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $quoteId = $create->json('id');

        $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => true],
            $this->authHeader(),
        )->assertOk();

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quoteId}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('tax_cents', 190_00)
            ->assertJsonPath('total_cents', 1_190_00);
    }

    #[Test]
    public function accepted_quote_is_immutable_when_tax_toggle_changes(): void
    {
        $this->setTaxEnabled(false);
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $quoteId = $create->json('id');

        $this->postJson(self::TENANT_BASE . "/api/v1/quotes/{$quoteId}/send", [], $this->authHeader())->assertOk();
        $this->postJson(self::TENANT_BASE . "/api/v1/quotes/{$quoteId}/accept", [], $this->authHeader())->assertOk();

        $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => true],
            $this->authHeader(),
        )->assertOk();

        $show = $this->getJson(
            self::TENANT_BASE . "/api/v1/quotes/{$quoteId}",
            $this->authHeader(),
        );

        $show->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('tax_cents', 0)
            ->assertJsonPath('total_cents', 1_000_00);
    }

    #[Test]
    public function client_specific_template_is_used_in_quote_pdf(): void
    {
        $client = $this->createClient();

        self::$sharedTenant->run(function () use ($client): void {
            DocumentTemplate::query()->create([
                'type' => 'quote',
                'client_id' => $client->id,
                'name' => 'Plantilla cliente ACME',
                'html_body' => '<html><body><h1>MARCADOR-CLIENTE-ACME</h1>{{client_name}}</body></html>',
                'is_default' => true,
            ]);
        });

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $quoteId = $create->json('id');

        $html = self::$sharedTenant->run(function () use ($quoteId) {
            $quote = Quote::query()->with('lines')->find($quoteId);
            $template = app(\App\Application\Documents\TemplateResolver::class)->resolve('quote', $quote->client_id);
            $variables = app(\App\Application\Documents\TemplateVariableBuilder::class)->forQuote($quote);

            return app(\App\Application\Documents\TemplateRenderer::class)->render($template->html_body, $variables);
        });

        $this->assertStringContainsString('MARCADOR-CLIENTE-ACME', $html);
        $this->assertStringContainsString('Acme Corp', $html);
    }

    #[Test]
    public function default_template_fallback_when_no_client_template(): void
    {
        $client = $this->createClient();

        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/quotes',
            ['client_id' => $client->id, 'lines' => $this->validLines()],
            $this->authHeader(),
        );

        $quoteId = $create->json('id');

        $templateName = self::$sharedTenant->run(function () use ($quoteId) {
            $quote = Quote::query()->find($quoteId);

            return app(\App\Application\Documents\TemplateResolver::class)
                ->resolve('quote', $quote->client_id)
                ->name;
        });

        $this->assertSame('Cotización predeterminada', $templateName);
    }

    #[Test]
    public function issued_billing_document_is_immutable_when_tax_toggle_changes(): void
    {
        Queue::fake();
        $this->setTaxEnabled(false);
        $client = $this->createClient();

        $project = self::$sharedTenant->run(function () use ($client) {
            return Project::query()->create([
                'client_id' => $client->id,
                'name' => 'Proyecto billing tax',
                'type' => 'freelance',
                'status' => 'active',
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_tax_id' => $client->tax_id,
                'client_address' => $client->address,
                'currency' => 'COP',
                'agreed_total_cents' => 1_000_00,
                'paid_total_cents' => 0,
                'balance_due_cents' => 1_000_00,
                'is_fully_paid' => false,
            ]);
        });

        $complete = $this->postJson(
            self::TENANT_BASE . "/api/v1/projects/{$project->id}/complete",
            [],
            $this->authHeader(),
        );

        $billingId = $complete->json('billing_document.id');

        $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => true],
            $this->authHeader(),
        )->assertOk();

        $billing = self::$sharedTenant->run(function () use ($billingId) {
            return BillingDocument::query()->find($billingId);
        });

        $this->assertSame(1_000_00, $billing->agreed_total_cents);
        $this->assertSame(1_000_00, $billing->balance_due_cents);
    }

    #[Test]
    public function document_templates_can_be_listed_and_updated(): void
    {
        $list = $this->getJson(
            self::TENANT_BASE . '/api/v1/document-templates?type=quote',
            $this->authHeader(),
        );

        $list->assertOk()->assertJsonCount(1, 'data');

        $templateId = $list->json('data.0.id');

        $update = $this->putJson(
            self::TENANT_BASE . "/api/v1/document-templates/{$templateId}",
            ['html_body' => '<html><body><h1>Plantilla editada</h1>{{client_name}}</body></html>'],
            $this->authHeader(),
        );

        $update->assertOk()
            ->assertJsonPath('html_body', '<html><body><h1>Plantilla editada</h1>{{client_name}}</body></html>');
    }

    #[Test]
    public function document_template_preview_returns_pdf(): void
    {
        $response = $this->post(
            self::TENANT_BASE . '/api/v1/document-templates/preview',
            [
                'type' => 'quote',
                'html_body' => '<html><body><h1>Vista previa</h1>{{client_name}}</body></html>',
            ],
            $this->authHeader(),
        );

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    #[Test]
    public function non_owner_user_cannot_update_settings_or_templates(): void
    {
        self::$sharedTenant->run(function (): void {
            User::query()->create([
                'name' => 'Member',
                'email' => 'member@test.localhost',
                'password' => Hash::make('secret-password'),
            ]);
        });

        $memberHeaders = $this->authHeaderFor('member@test.localhost', 'secret-password');

        $this->patchJson(
            self::TENANT_BASE . '/api/v1/settings',
            ['tax_enabled' => true],
            $memberHeaders,
        )->assertForbidden();

        $templateId = $this->getJson(
            self::TENANT_BASE . '/api/v1/document-templates?type=quote',
            $this->authHeader(),
        )->json('data.0.id');

        $this->putJson(
            self::TENANT_BASE . "/api/v1/document-templates/{$templateId}",
            ['html_body' => '<html><body><h1>Sin permiso</h1></body></html>'],
            $memberHeaders,
        )->assertForbidden();

        $this->post(
            self::TENANT_BASE . '/api/v1/document-templates/preview',
            [
                'type' => 'quote',
                'html_body' => '<html><body><h1>Sin permiso</h1></body></html>',
            ],
            $memberHeaders,
        )->assertForbidden();
    }

    #[Test]
    public function document_template_preview_requires_html_body_or_template_id(): void
    {
        $response = $this->postJson(
            self::TENANT_BASE . '/api/v1/document-templates/preview',
            [
                'type' => 'quote',
            ],
            $this->authHeader(),
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['html_body', 'template_id']);
    }
}
