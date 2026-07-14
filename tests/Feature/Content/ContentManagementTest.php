<?php

namespace Tests\Feature\Content;

use App\Enums\AdminLevel;
use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_editor_can_update_book_metadata_but_cannot_delete(): void
    {
        $editor = $this->admin(AdminLevel::Editor);
        $book = Book::factory()->create(['created_by' => $editor->id]);

        $this->actingAs($editor)->put("/admin/books/{$book->id}", [
            'title' => 'Judul Metadata Baru',
            'visibility' => BookVisibility::Public->value,
        ])->assertRedirect('/admin/books');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Judul Metadata Baru']);
        $this->actingAs($editor)->delete("/admin/books/{$book->id}")->assertForbidden();
    }

    public function test_content_admin_can_manage_categories_and_collections(): void
    {
        $admin = $this->admin(AdminLevel::ContentAdmin);

        $this->actingAs($admin)->post('/admin/categories', [
            'name' => 'Pendidikan Pemilih', 'status' => 'active', 'sort_order' => 2,
        ])->assertRedirect('/admin/categories');
        $category = Category::query()->where('name', 'Pendidikan Pemilih')->firstOrFail();

        $this->actingAs($admin)->put("/admin/categories/{$category->id}", [
            'name' => 'Pendidikan Demokrasi', 'status' => 'active', 'sort_order' => 1,
        ])->assertRedirect('/admin/categories');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Pendidikan Demokrasi']);

        $this->actingAs($admin)->post('/admin/collections', [
            'name' => 'Rak Utama', 'visibility' => 'public', 'status' => 'active', 'sort_order' => 1,
        ])->assertRedirect('/admin/collections');
        $this->assertDatabaseHas('collections', ['name' => 'Rak Utama', 'visibility' => 'public']);
    }

    public function test_auditor_has_read_only_content_access(): void
    {
        $auditor = $this->admin(AdminLevel::Auditor);

        $this->actingAs($auditor)->get('/admin/books')->assertOk();
        $this->actingAs($auditor)->post('/admin/categories', [
            'name' => 'Dilarang', 'status' => 'active',
        ])->assertForbidden();
    }

    public function test_dashboard_displays_catalog_metrics(): void
    {
        $auditor = $this->admin(AdminLevel::Auditor);
        Book::factory()->count(2)->create(['status' => BookStatus::Draft]);
        Book::factory()->create(['status' => BookStatus::Published, 'published_at' => now()]);
        Book::factory()->create(['visibility' => BookVisibility::Private]);

        $this->actingAs($auditor)->get('/admin')
            ->assertOk()
            ->assertSee('Ringkasan')
            ->assertSee('data-visit-chart', false)
            ->assertSee('4')
            ->assertSee('2');
    }

    private function admin(AdminLevel $level): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'admin_level' => $level]);
    }
}
