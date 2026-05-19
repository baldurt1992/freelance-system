<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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

    /** @var list<string> */
    protected array $connectionsToTransact = [];

    protected static bool $tenantBootstrapped = false;

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

        if (self::$tenantBootstrapped) {
            return;
        }

        $tenantDbPath = database_path('tenanttest');
        if (file_exists($tenantDbPath)) {
            unlink($tenantDbPath);
        }

        self::$sharedTenant = Tenant::create([
            'id' => 'test',
            'name' => 'Test Workspace',
            'data' => [
                'tax_enabled' => false,
                'currency' => 'COP',
            ],
        ]);

        self::$sharedTenant->domains()->create(['domain' => 'test.localhost']);

        self::$sharedTenant->run(function (): void {
            User::query()->create([
                'name' => 'Owner',
                'email' => 'owner@test.localhost',
                'password' => Hash::make('secret-password'),
            ]);
        });

        self::$tenantBootstrapped = true;
    }
}
