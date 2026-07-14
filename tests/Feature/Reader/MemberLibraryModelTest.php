<?php

namespace Tests\Feature\Reader;

use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Favorite;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLibraryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_store_favorite_progress_and_page_bookmark(): void
    {
        $member = User::factory()->create();
        $book = Book::factory()->create();

        $favorite = $member->favorites()->create(['book_id' => $book->id]);
        $history = $member->readingHistories()->create([
            'book_id' => $book->id,
            'last_page' => 7,
            'duration_seconds' => 125,
            'last_read_at' => now(),
        ]);
        $bookmark = $member->bookmarks()->create([
            'book_id' => $book->id,
            'page' => 7,
            'label' => 'Bagian penting',
            'note' => 'Baca ulang bagian ini.',
        ]);

        $this->assertInstanceOf(Favorite::class, $favorite);
        $this->assertInstanceOf(ReadingHistory::class, $history);
        $this->assertInstanceOf(Bookmark::class, $bookmark);
        $this->assertTrue($book->favoritedBy()->whereKey($member)->exists());
        $this->assertSame(7, $book->readingHistories()->firstOrFail()->last_page);
        $this->assertSame('Bagian penting', $book->bookmarks()->firstOrFail()->label);
    }

    public function test_member_library_constraints_prevent_duplicate_rows(): void
    {
        $member = User::factory()->create();
        $book = Book::factory()->create();

        $member->favorites()->create(['book_id' => $book->id]);
        $member->readingHistories()->create(['book_id' => $book->id]);
        $member->bookmarks()->create(['book_id' => $book->id, 'page' => 3]);

        $this->expectException(UniqueConstraintViolationException::class);
        $member->favorites()->create(['book_id' => $book->id]);
    }
}
