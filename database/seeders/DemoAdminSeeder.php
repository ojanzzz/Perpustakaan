<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) (env('DEMO_ACCOUNT_PASSWORD') ?: 'KpuDemo!2026');

        $accounts = [
            AdminLevel::Superadmin->value => ['Super Administrator', 'superadmin@demo.test'],
            AdminLevel::ContentAdmin->value => ['Administrator Konten', 'content.admin@demo.test'],
            AdminLevel::Editor->value => ['Editor', 'editor@demo.test'],
            AdminLevel::Auditor->value => ['Auditor', 'auditor@demo.test'],
        ];

        foreach ($accounts as $level => [$name, $email]) {
            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'role' => UserRole::Admin,
                    'admin_level' => $level,
                    'status' => AccountStatus::Active,
                    'email_verified_at' => now(),
                ]
            );
        }

        User::query()->updateOrCreate(
            ['email' => 'member@demo.test'],
            [
                'name' => 'Anggota Demo',
                'password' => $password,
                'role' => UserRole::Member,
                'admin_level' => null,
                'status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ],
        );
    }
}
