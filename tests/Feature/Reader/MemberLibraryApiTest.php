<?php

namespace Tests\Feature\Reader;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLibraryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_write_member_library_state(): void
    {
        $book = $this->publishedBook();

        $this->putJson("/api/member/books/{$book->id}/progress", ['page' => 2, 'duration_delta' => 10])
            ->assertUnauthorized();
    }

    public function test_member_can_toggle_favorite_and_manage_bookmark(): void
    {
        $member = User::factory()->create();
        $book = $this->publishedBook();

        $this->actingAs($member)->putJson("/api/member/books/{$book->id}/favorite")
            ->assertOk()->assertJsonPath('favorited', true);
        $this->assertDatabaseHas('favorites', ['user_id' => $member->id, 'book_id' => $book->id]);

        $this->actingAs($member)->postJson("/api/member/books/{$book->id}/bookmarks", [
            'page' => 5,
            'label' => 'Ringkasan',
            'note' => 'Bagian utama',
        ])->assertCreated()->assertJsonPath('bookmark.page', 5);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $member->id, 'book_id' => $book->id, 'page' => 5]);

        $this->actingAs($member)->deleteJson("/api/member/books/{$book->id}/bookmarks/5")->assertNoContent();
        $this->actingAs($member)->deleteJson("/api/member/books/{$book->id}/favorite")
            ->assertOk()->assertJsonPath('favorited', false);
    }

    public function test_progress_is_upserted_and_duration_is_accumulated_safely(): void
    {
        $member = User::factory()->create();
        $book = $this->publishedBook(['page_count' => 12]);

        $this->actingAs($member)->putJson("/api/member/books/{$book->id}/progress", [
            'page' => 4,
            'duration_delta' => 60,
        ])->assertOk()->assertJsonPath('progress.duration_seconds', 60);

        $this->actingAs($member)->putJson("/api/member/books/{$book->id}/progress", [
            'page' => 7,
            'duration_delta' => 20,
        ])->assertOk()
            ->assertJsonPath('progress.last_page', 7)
            ->assertJsonPath('progress.duration_seconds', 80);

        $this->actingAs($member)->putJson("/api/member/books/{$book->id}/progress", [
            'page' => 13,
            'duration_delta' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('page');
    }

    public function test_member_cannot_write_state_for_an_inaccessible_book(): void
    {
        $member = User::factory()->create();
        $private = $this->publishedBook(['visibility' => BookVisibility::Private]);

        $this->actingAs($member)->putJson("/api/member/books/{$private->id}/favorite")->assertNotFound();
    }

    private function publishedBook(array $attributes = []): Book
    {
        return Book::factory()->create([
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
            'page_count' => 10,
            ...$attributes,
        ]);
    }
}
