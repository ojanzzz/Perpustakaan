<?php

namespace Tests\Feature\PublicPortal;

use App\Domain\Catalog\BookAccessService;
use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicBookAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_discovery_only_contains_currently_published_records_visible_to_the_actor(): void
    {
        $public = $this->publishedBook(['title' => 'Panduan Pemilih', 'visibility' => BookVisibility::Public]);
        $memberOnly = $this->publishedBook(['title' => 'Modul Anggota', 'visibility' => BookVisibility::Role]);
        $this->publishedBook(['title' => 'Tautan Rahasia', 'visibility' => BookVisibility::Unlisted]);
        $this->publishedBook(['title' => 'Sudah Kedaluwarsa', 'visibility' => BookVisibility::Expiring, 'expires_at' => now()->subMinute()]);
        Book::factory()->create(['title' => 'Masih Draft', 'status' => BookStatus::Draft]);

        $service = app(BookAccessService::class);

        $this->assertEqualsCanonicalizing([$public->id], $service->discoverableQuery(null)->pluck('id')->all());

        $member = User::factory()->create();
        $this->assertEqualsCanonicalizing(
            [$public->id, $memberOnly->id],
            $service->discoverableQuery($member)->pluck('id')->all(),
        );

        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $this->assertEqualsCanonicalizing(
            [$public->id, $memberOnly->id],
            $service->discoverableQuery($superadmin)->pluck('id')->all(),
        );
    }

    public function test_direct_access_supports_unlisted_verified_member_and_password_rules_without_exposing_private_books(): void
    {
        $service = app(BookAccessService::class);
        $member = User::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
        $unverified = User::factory()->unverified()->create();

        $unlisted = $this->publishedBook(['visibility' => BookVisibility::Unlisted]);
        $verified = $this->publishedBook(['visibility' => BookVisibility::VerifiedEmail]);
        $memberOnly = $this->publishedBook(['visibility' => BookVisibility::Role]);
        $password = $this->publishedBook(['visibility' => BookVisibility::Password, 'password_hash' => Hash::make('Rahasia-2026')]);
        $private = $this->publishedBook(['visibility' => BookVisibility::Private]);

        $this->assertTrue($service->canView($unlisted));
        $this->assertTrue($service->canView($verified, $member));
        $this->assertFalse($service->canView($verified, $unverified));
        $this->assertTrue($service->canView($memberOnly, $member));
        $this->assertFalse($service->canView($memberOnly));
        $this->assertFalse($service->canView($password));
        $this->assertTrue($service->canView($password, passwordUnlocked: true));
        $this->assertFalse($service->canView($private, $member));
        $this->assertTrue($service->canView($private, $superadmin));
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
