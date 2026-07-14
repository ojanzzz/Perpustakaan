<?php

namespace Tests\Feature\Content;

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

    public function test_superadmin_controls_the_full_publication_workflow_and_member_is_denied(): void
    {
        $this->seed(PermissionSeeder::class);
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $member = User::factory()->create(['role' => UserRole::Member]);
        $book = Book::factory()->create(['status' => BookStatus::Draft, 'created_by' => $superadmin->id]);

        $this->actingAs($superadmin)->post("/admin/books/{$book->id}/submit", ['notes' => 'Siap ditinjau'])->assertRedirect();
        $this->assertSame(BookStatus::PendingReview, $book->fresh()->status);
        $this->actingAs($member)->post("/admin/books/{$book->id}/publish")->assertForbidden();

        $this->actingAs($superadmin)->post("/admin/books/{$book->id}/return", ['notes' => 'Perbaiki metadata penerbit.'])->assertRedirect();
        $this->assertSame(BookStatus::Draft, $book->fresh()->status);
        $this->assertDatabaseHas('book_reviews', ['book_id' => $book->id, 'action' => 'returned', 'notes' => 'Perbaiki metadata penerbit.']);

        $this->actingAs($superadmin)->post("/admin/books/{$book->id}/submit")->assertRedirect();
        $this->actingAs($superadmin)->post("/admin/books/{$book->id}/publish", ['published_at' => now()->toDateTimeString()])->assertRedirect();
        $this->assertSame(BookStatus::Published, $book->fresh()->status);
        $this->assertNotNull($book->fresh()->published_at);
    }
}
