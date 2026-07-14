<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) (env('DEMO_ACCOUNT_PASSWORD') ?: 'KpuDemo!2026');

        User::query()->updateOrCreate(
            ['email' => 'superadmin@demo.test'],
            [
                'name' => 'Super Administrator',
                'password' => $password,
                'role' => UserRole::Superadmin,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'member@demo.test'],
            [
                'name' => 'Anggota Demo',
                'password' => $password,
                'role' => UserRole::Member,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        User::query()
            ->whereIn('email', [
                'content.admin@demo.test',
                'editor@demo.test',
                'auditor@demo.test',
            ])
            ->update([
                'role' => UserRole::Member->value,
                'status' => AccountStatus::Inactive->value,
            ]);
    }
}
