<?php

namespace Tests\Feature\Content;

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

    public function test_superadmin_can_update_and_delete_book_metadata(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $book = Book::factory()->create(['created_by' => $superadmin->id]);

        $this->actingAs($superadmin)->put("/admin/books/{$book->id}", [
            'title' => 'Judul Metadata Baru',
            'visibility' => BookVisibility::Public->value,
        ])->assertRedirect('/admin/books');

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Judul Metadata Baru']);
        $this->actingAs($superadmin)->delete("/admin/books/{$book->id}")->assertRedirect('/admin/books');
        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    public function test_superadmin_can_manage_categories_and_collections(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);

        $this->actingAs($superadmin)->post('/admin/categories', [
            'name' => 'Pendidikan Pemilih', 'status' => 'active', 'sort_order' => 2,
        ])->assertRedirect('/admin/categories');
        $category = Category::query()->where('name', 'Pendidikan Pemilih')->firstOrFail();

        $this->actingAs($superadmin)->put("/admin/categories/{$category->id}", [
            'name' => 'Pendidikan Demokrasi', 'status' => 'active', 'sort_order' => 1,
        ])->assertRedirect('/admin/categories');
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Pendidikan Demokrasi']);

        $this->actingAs($superadmin)->post('/admin/collections', [
            'name' => 'Rak Utama', 'visibility' => 'public', 'status' => 'active', 'sort_order' => 1,
        ])->assertRedirect('/admin/collections');
        $this->assertDatabaseHas('collections', ['name' => 'Rak Utama', 'visibility' => 'public']);
    }

    public function test_member_cannot_access_content_dashboard_or_mutations(): void
    {
        $member = User::factory()->create(['role' => UserRole::Member]);

        $this->actingAs($member)->get('/admin/books')->assertForbidden();
        $this->actingAs($member)->post('/admin/categories', [
            'name' => 'Dilarang', 'status' => 'active',
        ])->assertForbidden();
    }

    public function test_dashboard_displays_catalog_metrics(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        Book::factory()->count(2)->create(['status' => BookStatus::Draft]);
        Book::factory()->create(['status' => BookStatus::Published, 'published_at' => now()]);
        Book::factory()->create(['visibility' => BookVisibility::Private]);

        $this->actingAs($superadmin)->get('/admin')
            ->assertOk()
            ->assertSee('Ringkasan')
            ->assertSee('data-visit-chart', false)
            ->assertSee('4')
            ->assertSee('2');
    }
}
