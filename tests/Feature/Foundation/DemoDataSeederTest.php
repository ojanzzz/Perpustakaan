<?php

namespace Tests\Feature\Foundation;

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\PersonalCollection;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoActivitySeeder;
use Database\Seeders\DemoAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_creates_only_superadmin_and_member_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('role', UserRole::Superadmin)->count());
        $this->assertSame(1, User::query()->where('role', UserRole::Member)->count());
        $this->assertSame(0, User::query()->where('role', UserRole::Public)->count());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@demo.test',
            'role' => UserRole::Superadmin->value,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'member@demo.test',
            'role' => UserRole::Member->value,
            'status' => 'active',
        ]);

        $this->assertGreaterThanOrEqual(20, DB::table('permissions')->count());
        $this->assertSame(DB::table('permissions')->count(), DB::table('role_permissions')->count());
        $this->assertDatabaseMissing('role_permissions', ['role' => UserRole::Member->value]);
        $this->assertDatabaseMissing('role_permissions', ['role' => UserRole::Public->value]);
        $this->assertTrue(Hash::check(
            'KpuDemo!2026',
            User::query()->where('role', UserRole::Superadmin)->firstOrFail()->password
        ));
    }

    public function test_development_seed_populates_member_library_and_analytics_samples_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $member = User::query()->where('role', UserRole::Member)->firstOrFail();

        $this->assertSame(20, Book::query()->count());
        $this->assertDatabaseCount('favorites', 3);
        $this->assertDatabaseCount('reading_histories', 4);
        $this->assertDatabaseCount('bookmarks', 3);
        $this->assertDatabaseCount('personal_collections', 1);
        $this->assertDatabaseCount('personal_collection_books', 4);
        $this->assertDatabaseCount('category_subscriptions', 2);
        $this->assertDatabaseCount('book_downloads', 8);
        $this->assertDatabaseCount('search_logs', 8);
        $this->assertDatabaseCount('feedback', 2);
        $this->assertDatabaseCount('audit_logs', 4);
        $this->assertDatabaseHas('reading_histories', [
            'user_id' => $member->id,
            'last_page' => 7,
        ]);
        $this->assertDatabaseHas('search_logs', [
            'normalized_query' => 'jadwal pemilu luar negeri',
            'result_count' => 0,
        ]);
    }

    public function test_demo_admin_seed_disables_accounts_from_removed_demo_levels(): void
    {
        $legacyDemoAccount = User::factory()->create([
            'email' => 'editor@demo.test',
            'role' => UserRole::Member,
            'status' => 'active',
        ]);

        $this->seed(DemoAdminSeeder::class);

        $this->assertDatabaseHas('users', [
            'id' => $legacyDemoAccount->id,
            'role' => UserRole::Member->value,
            'status' => 'inactive',
        ]);
    }

    public function test_activity_seed_is_isolated_from_existing_data_and_restores_its_soft_deleted_collection(): void
    {
        $this->seed(DatabaseSeeder::class);

        $externalBook = Book::factory()->create(['sort_order' => 0]);
        $externalCategory = Category::factory()->create(['sort_order' => 0]);
        $demoCollection = PersonalCollection::query()->where('slug', 'bacaan-pilihan')->firstOrFail();
        $demoCollection->delete();

        $this->seed(DemoActivitySeeder::class);

        $this->assertDatabaseMissing('favorites', ['book_id' => $externalBook->id]);
        $this->assertDatabaseMissing('reading_histories', ['book_id' => $externalBook->id]);
        $this->assertDatabaseMissing('book_downloads', ['book_id' => $externalBook->id]);
        $this->assertDatabaseMissing('category_subscriptions', ['category_id' => $externalCategory->id]);
        $this->assertDatabaseHas('personal_collections', [
            'id' => $demoCollection->id,
            'deleted_at' => null,
        ]);
        $this->assertSame(1, PersonalCollection::withTrashed()->where('slug', 'bacaan-pilihan')->count());
    }
}
