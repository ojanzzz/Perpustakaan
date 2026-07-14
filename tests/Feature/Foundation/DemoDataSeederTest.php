<?php

namespace Tests\Feature\Foundation;

use App\Enums\AdminLevel;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\PersonalCollection;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            $this->assertSame(1, User::query()->where('admin_level', $level)->count());
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
        $this->assertTrue(Hash::check(
            'KpuDemo!2026',
            User::query()->where('admin_level', AdminLevel::Superadmin)->firstOrFail()->password
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
