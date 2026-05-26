<?php

declare(strict_types=1);

namespace Tests;

use App\Models\DocumentTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Feature tests that need a real Stancl tenant DB (SQLite file per tenant).
 * Do not use RefreshDatabase here — it conflicts with tenant DB lifecycle.
 */
abstract class TenantTestCase extends TestCase
{
    use DatabaseMigrations;

    private const TENANT_ID = 'test';

    private const TENANT_DOMAIN = 'test.localhost';

    /** @var list<string> */
    protected array $connectionsToTransact = [];

    protected static bool $tenantDatabasePrepared = false;

    protected static Tenant $sharedTenant;

    protected function setUp(): void
    {
        parent::setUp();

        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        DB::purge('tenant');
        DB::setDefaultConnection((string) config('database.default'));

        Cache::flush();

        $tenantDatabaseExists = file_exists($this->tenantDatabasePath());

        if (! self::$tenantDatabasePrepared || ! $tenantDatabaseExists) {
            $this->bootstrapFreshTenantDatabase();
        } else {
            $this->restoreCentralTenantRecord();
        }

        self::$sharedTenant->run(function (): void {
            $this->resetTenantTables();
            $this->seedTenantFixtures();
        });

        tenancy()->end();
        DB::purge('tenant');
        DB::setDefaultConnection((string) config('database.default'));
    }

    private function bootstrapFreshTenantDatabase(): void
    {
        $tenantDbPath = $this->tenantDatabasePath();

        if (is_file($tenantDbPath) && ! @unlink($tenantDbPath)) {
            throw new \RuntimeException("Unable to remove stale tenant test database [{$tenantDbPath}].");
        }

        self::$sharedTenant = Tenant::query()->create($this->tenantAttributes());
        self::$sharedTenant->domains()->create(['domain' => self::TENANT_DOMAIN]);

        Artisan::call('tenants:migrate', ['--tenants' => [self::TENANT_ID]]);

        self::$tenantDatabasePrepared = true;
    }

    private function restoreCentralTenantRecord(): void
    {
        self::$sharedTenant = Tenant::withoutEvents(function (): Tenant {
            return Tenant::query()->create($this->tenantAttributes());
        });

        self::$sharedTenant->domains()->create(['domain' => self::TENANT_DOMAIN]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantAttributes(): array
    {
        return [
            'id' => self::TENANT_ID,
            'name' => 'Test Workspace',
            'tax_enabled' => false,
            'currency' => 'COP',
        ];
    }

    private function tenantDatabasePath(): string
    {
        return database_path('tenant' . self::TENANT_ID);
    }

    private function resetTenantTables(): void
    {
        $connection = DB::connection('tenant');
        $tables = $connection->select(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
        );

        $connection->statement('PRAGMA foreign_keys = OFF');

        foreach ($tables as $table) {
            $tableName = (string) $table->name;

            if ($tableName === 'migrations') {
                continue;
            }

            $connection->table($tableName)->delete();
        }

        $connection->statement('DELETE FROM sqlite_sequence');
        $connection->statement('PRAGMA foreign_keys = ON');
    }

    private function seedTenantFixtures(): void
    {
        User::query()->create([
            'name' => 'Owner',
            'email' => 'owner@test.localhost',
            'password' => Hash::make('secret-password'),
        ]);

        $now = Carbon::now();

        DocumentTemplate::query()->insert([
            [
                'type' => 'quote',
                'client_id' => null,
                'name' => 'Cotización predeterminada',
                'html_body' => (string) file_get_contents(resource_path('templates/defaults/quote.html')),
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'billing',
                'client_id' => null,
                'name' => 'Cuenta de cobro predeterminada',
                'html_body' => (string) file_get_contents(resource_path('templates/defaults/billing.html')),
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
