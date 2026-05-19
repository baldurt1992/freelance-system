<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantProvisionCommand extends Command
{
    protected $signature = 'tenant:provision
        {slug : Tenant id / slug (e.g. personal)}
        {--name= : Display name}
        {--domain= : Primary domain (e.g. personal.localhost)}
        {--email= : Owner email}
        {--password= : Owner password (random if omitted)}
        {--currency=COP : Default currency code}
        {--tax-enabled : Enable VAT breakdown for this tenant}';

    protected $description = 'Create a tenant (DB + migrations) and seed the owner user';

    public function handle(): int
    {
        $slug = Str::lower($this->argument('slug'));
        $name = (string) ($this->option('name') ?: Str::title($slug));
        $domain = (string) ($this->option('domain') ?: "{$slug}.localhost");
        $email = (string) ($this->option('email') ?: "owner@{$slug}.test");
        $password = (string) ($this->option('password') ?: Str::password(16));
        $currency = strtoupper((string) $this->option('currency'));
        $taxEnabled = (bool) $this->option('tax-enabled');

        if (Tenant::query()->where('id', $slug)->exists()) {
            $this->error("Tenant [{$slug}] already exists.");

            return self::FAILURE;
        }

        $this->info("Provisioning tenant [{$slug}]…");

        $tenant = Tenant::create([
            'id' => $slug,
            'name' => $name,
            'data' => [
                'tax_enabled' => $taxEnabled,
                'currency' => $currency,
            ],
        ]);

        $tenant->domains()->create(['domain' => $domain]);

        $tenant->run(function () use ($name, $email, $password): void {
            User::query()->create([
                'name' => $name . ' Owner',
                'email' => $email,
                'password' => Hash::make($password),
            ]);
        });

        $this->components->twoColumnDetail('Tenant ID', $slug);
        $this->components->twoColumnDetail('Domain', $domain);
        $this->components->twoColumnDetail('API base', "http://{$domain}/api/v1");
        $this->components->twoColumnDetail('Owner email', $email);
        $this->components->twoColumnDetail('Owner password', $password);
        $this->components->twoColumnDetail('tax_enabled', $taxEnabled ? 'true' : 'false');
        $this->components->twoColumnDetail('currency', $currency);

        $this->newLine();
        $this->comment('Store the password securely; it is not shown again.');

        return self::SUCCESS;
    }
}
