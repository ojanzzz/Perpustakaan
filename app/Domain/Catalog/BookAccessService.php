<?php

namespace App\Domain\Catalog;

use App\Enums\AccountStatus;
use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BookAccessService
{
    public function passwordSessionKey(Book $book): string
    {
        return 'books.unlocked.'.$book->id;
    }

    /** @return Builder<Book> */
    public function discoverableQuery(?User $user): Builder
    {
        $query = Book::query();
        $this->applyPublicationWindow($query);

        $visible = [BookVisibility::Public->value, BookVisibility::Expiring->value, BookVisibility::Scheduled->value];
        if ($this->isActiveUser($user)) {
            $visible[] = BookVisibility::Role->value;
        }
        if ($this->isVerifiedUser($user)) {
            $visible[] = BookVisibility::VerifiedEmail->value;
        }

        return $query->whereIn('visibility', $visible);
    }

    public function canView(Book $book, ?User $user = null, bool $passwordUnlocked = false): bool
    {
        if (! $this->isCurrentlyPublished($book)) {
            return false;
        }

        return match ($book->visibility) {
            BookVisibility::Public, BookVisibility::Unlisted,
            BookVisibility::Scheduled, BookVisibility::Expiring => true,
            BookVisibility::Role => $this->isActiveUser($user),
            BookVisibility::VerifiedEmail => $this->isVerifiedUser($user),
            BookVisibility::Password => $passwordUnlocked,
            BookVisibility::Private => $this->isActiveAdmin($user),
        };
    }

    public function isPasswordLocked(Book $book, bool $passwordUnlocked): bool
    {
        return $this->isCurrentlyPublished($book)
            && $book->visibility === BookVisibility::Password
            && ! $passwordUnlocked;
    }

    /** @param Builder<Book> $query */
    public function applyPublicationWindow(Builder $query): Builder
    {
        return $query
            ->where('status', BookStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    private function isCurrentlyPublished(Book $book): bool
    {
        return $book->status === BookStatus::Published
            && $book->published_at !== null
            && $book->published_at->isPast()
            && ($book->expires_at === null || $book->expires_at->isFuture());
    }

    private function isActiveUser(?User $user): bool
    {
        return $user !== null
            && $user->status === AccountStatus::Active
            && in_array($user->role, [UserRole::Member, UserRole::Admin], true);
    }

    private function isVerifiedUser(?User $user): bool
    {
        return $this->isActiveUser($user) && $user->hasVerifiedEmail();
    }

    private function isActiveAdmin(?User $user): bool
    {
        return $this->isActiveUser($user) && $user->role === UserRole::Admin;
    }
}
