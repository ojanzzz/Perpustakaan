<?php

namespace Tests\Feature\Foundation;

use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_creates_each_admin_level_and_permission_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (AdminLevel::cases() as $level) {
            $this->assertDatabaseHas('users', [
                'role' => UserRole::Admin->value,
                'admin_level' => $level->value,
                'status' => 'active',
            ]);
        }

        $this->assertGreaterThanOrEqual(20, DB::table('permissions')->count());
        $this->assertTrue(
            User::query()->where('admin_level', AdminLevel::Editor)->firstOrFail()
                ->can('books.create')
        );
        $this->assertFalse(
            User::query()->where('admin_level', AdminLevel::Editor)->firstOrFail()
                ->can('books.publish')
        );
    }
}
