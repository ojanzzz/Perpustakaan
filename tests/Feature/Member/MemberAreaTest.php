<?php

namespace Tests\Feature\Member;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Notifications\BookPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_pages_require_authentication(): void
    {
        $this->get('/profil')->assertRedirect('/login');
        $this->get('/favorit')->assertRedirect('/login');
        $this->get('/riwayat-baca')->assertRedirect('/login');
    }

    public function test_member_can_update_profile_and_password(): void
    {
        $member = User::factory()->create(['password' => 'Password!123']);

        $this->actingAs($member)->put('/profil', ['name' => 'Nama Anggota', 'email' => 'anggota.baru@example.test'])
            ->assertRedirect('/profil');
        $this->assertSame('Nama Anggota', $member->refresh()->name);
        $this->assertNull($member->email_verified_at);

        $this->actingAs($member)->put('/profil/password', [
            'current_password' => 'Password!123',
            'password' => 'Password!456',
            'password_confirmation' => 'Password!456',
        ])->assertRedirect('/profil');
        $this->assertTrue(Hash::check('Password!456', $member->refresh()->password));
    }

    public function test_library_pages_show_favorites_history_and_bookmarks(): void
    {
        $member = User::factory()->create();
        $book = $this->publishedBook(['title' => 'Buku Anggota']);
        $member->favorites()->create(['book_id' => $book->id]);
        $member->readingHistories()->create(['book_id' => $book->id, 'last_page' => 5, 'last_read_at' => now()]);
        $member->bookmarks()->create(['book_id' => $book->id, 'page' => 3, 'label' => 'Penting']);

        $this->actingAs($member)->get('/favorit')->assertOk()->assertSee('Buku Anggota');
        $this->actingAs($member)->get('/riwayat-baca')->assertOk()->assertSee('Halaman 5');
        $this->actingAs($member)->get('/bookmark')->assertOk()->assertSee('Penting');
    }

    public function test_member_can_create_personal_collection_and_add_a_book(): void
    {
        $member = User::factory()->create();
        $book = $this->publishedBook();

        $this->actingAs($member)->post('/koleksi-saya', ['name' => 'Bacaan Mingguan', 'description' => 'Pilihan saya'])
            ->assertRedirect('/koleksi-saya');
        $collectionId = $member->personalCollections()->value('id');

        $this->actingAs($member)->post("/koleksi-saya/{$collectionId}/buku", ['book_id' => $book->id])
            ->assertRedirect();
        $this->assertDatabaseHas('personal_collection_books', ['personal_collection_id' => $collectionId, 'book_id' => $book->id]);
    }

    public function test_member_can_subscribe_to_category_and_read_notifications(): void
    {
        $member = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($member)->put("/langganan/{$category->id}")->assertRedirect('/langganan');
        $this->assertDatabaseHas('category_subscriptions', ['user_id' => $member->id, 'category_id' => $category->id]);

        $notification = $member->notifications()->create([
            'id' => fake()->uuid(), 'type' => 'book.published',
            'data' => ['title' => 'Publikasi baru', 'url' => '/katalog'],
        ]);
        $this->actingAs($member)->put("/notifikasi/{$notification->id}/baca")->assertRedirect('/notifikasi');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_category_subscriber_receives_database_notification_when_book_is_published(): void
    {
        $member = User::factory()->create();
        $category = Category::factory()->create();
        $member->subscribedCategories()->attach($category);
        $book = Book::factory()->create(['status' => BookStatus::Draft, 'published_at' => null]);
        $book->categories()->attach($category);

        $book->update(['status' => BookStatus::Published, 'published_at' => now()]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $member->id,
            'type' => BookPublishedNotification::class,
        ]);
    }

    public function test_member_can_soft_delete_account_with_current_password(): void
    {
        $member = User::factory()->create(['password' => 'Password!123']);

        $this->actingAs($member)->delete('/akun', ['password' => 'Password!123'])
            ->assertRedirect('/');
        $this->assertSoftDeleted('users', ['id' => $member->id]);
        $this->assertGuest();
    }

    private function publishedBook(array $attributes = []): Book
    {
        return Book::factory()->create([
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
            ...$attributes,
        ]);
    }
}
