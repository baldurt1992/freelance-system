<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\FinanceCategory;
use App\Models\FinanceEntry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantTestCase;

final class FinanceCategoryTest extends TenantTestCase
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
    public function categories_can_be_created_listed_updated_and_deleted(): void
    {
        $create = $this->postJson(
            self::TENANT_BASE . '/api/v1/finances/categories',
            ['type' => 'expense', 'name' => 'Suscripciones SaaS'],
            $this->authHeader(),
        );

        $create->assertCreated()
            ->assertJsonPath('type', 'expense')
            ->assertJsonPath('slug', 'suscripciones_saas')
            ->assertJsonPath('name', 'Suscripciones SaaS');

        $id = (int) $create->json('id');

        $this->getJson(
            self::TENANT_BASE . '/api/v1/finances/categories?type=expense',
            $this->authHeader(),
        )->assertOk()
            ->assertJsonFragment([
                'id' => $id,
                'type' => 'expense',
                'slug' => 'suscripciones_saas',
                'name' => 'Suscripciones SaaS',
            ]);

        $this->patchJson(
            self::TENANT_BASE . "/api/v1/finances/categories/{$id}",
            ['name' => 'Suscripciones y licencias'],
            $this->authHeader(),
        )->assertOk()
            ->assertJsonPath('slug', 'suscripciones_y_licencias')
            ->assertJsonPath('name', 'Suscripciones y licencias');

        $this->deleteJson(
            self::TENANT_BASE . "/api/v1/finances/categories/{$id}",
            [],
            $this->authHeader(),
        )->assertNoContent();

        $this->assertNull(self::$sharedTenant->run(fn () => FinanceCategory::query()->find($id)));
    }

    #[Test]
    public function updating_category_refreshes_snapshot_slug_in_manual_entries(): void
    {
        $category = self::$sharedTenant->run(function () {
            return FinanceCategory::query()->create([
                'type' => 'expense',
                'slug' => 'office_snacks',
                'name' => 'Office Snacks',
            ]);
        });

        $entryId = self::$sharedTenant->run(function () use ($category): int {
            return (int) FinanceEntry::query()->create([
                'type' => 'expense',
                'amount_cents' => 2500,
                'occurred_on' => '2026-06-07',
                'name' => 'Snacks oficina',
                'description' => 'Snacks',
                'category' => $category->slug,
                'finance_category_id' => $category->id,
                'source_type' => 'manual',
                'source_id' => null,
                'is_manual' => true,
            ])->id;
        });

        $this->patchJson(
            self::TENANT_BASE . "/api/v1/finances/categories/{$category->id}",
            ['name' => 'Snacks de oficina'],
            $this->authHeader(),
        )->assertOk()
            ->assertJsonPath('slug', 'snacks_de_oficina');

        self::$sharedTenant->run(function () use ($entryId, $category): void {
            $entry = FinanceEntry::query()->findOrFail($entryId);
            $category = FinanceCategory::query()->findOrFail($category->id);

            $this->assertSame('snacks_de_oficina', $entry->category);
            $this->assertSame($category->id, $entry->finance_category_id);
        });
    }
}
