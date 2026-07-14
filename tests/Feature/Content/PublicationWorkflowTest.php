<?php

namespace Tests\Feature\Content;

use App\Enums\AdminLevel;
use App\Enums\BookStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_submits_and_content_admin_returns_then_publishes(): void
    {
        $this->seed(PermissionSeeder::class);
        $editor = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::Editor]);
        $content = User::factory()->create(['role' => UserRole::Admin, 'admin_level' => AdminLevel::ContentAdmin]);
        $book = Book::factory()->create(['status' => BookStatus::Draft, 'created_by' => $editor->id]);

        $this->actingAs($editor)->post("/admin/books/{$book->id}/submit", ['notes' => 'Siap ditinjau'])->assertRedirect();
        $this->assertSame(BookStatus::PendingReview, $book->fresh()->status);
        $this->actingAs($editor)->post("/admin/books/{$book->id}/publish")->assertForbidden();

        $this->actingAs($content)->post("/admin/books/{$book->id}/return", ['notes' => 'Perbaiki metadata penerbit.'])->assertRedirect();
        $this->assertSame(BookStatus::Draft, $book->fresh()->status);
        $this->assertDatabaseHas('book_reviews', ['book_id' => $book->id, 'action' => 'returned', 'notes' => 'Perbaiki metadata penerbit.']);

        $this->actingAs($editor)->post("/admin/books/{$book->id}/submit")->assertRedirect();
        $this->actingAs($content)->post("/admin/books/{$book->id}/publish", ['published_at' => now()->toDateTimeString()])->assertRedirect();
        $this->assertSame(BookStatus::Published, $book->fresh()->status);
        $this->assertNotNull($book->fresh()->published_at);
    }
}
