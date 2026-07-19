<?php

namespace Tests\Feature\Content;

use App\Enums\BookStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_publication_workflow_routes_and_edit_controls_are_removed(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $book = Book::factory()->create([
            'status' => BookStatus::Published,
            'published_at' => now(),
            'created_by' => $superadmin->id,
        ]);

        $this->assertFalse(Route::has('admin.books.submit'));
        $this->assertFalse(Route::has('admin.books.return'));
        $this->assertFalse(Route::has('admin.books.publish'));
        $this->assertFalse(Route::has('admin.books.archive'));
        $this->assertSame(0, DB::table('permissions')->whereIn('name', [
            'books.submit',
            'books.review',
            'books.publish',
            'books.schedule',
            'books.archive',
        ])->count());

        foreach (['submit', 'return', 'publish', 'archive'] as $action) {
            $this->actingAs($superadmin)->post("/admin/books/{$book->id}/{$action}")->assertNotFound();
        }

        $this->actingAs($superadmin)->get("/admin/books/{$book->id}/edit")
            ->assertOk()
            ->assertDontSee('Alur publikasi')
            ->assertDontSee('Kirim untuk ditinjau')
            ->assertSee('images/logo.png', false);
    }
}
