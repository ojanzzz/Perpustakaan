<?php

namespace App\Http\Controllers\Reader;

use App\Domain\Catalog\BookAccessService;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reader\StoreBookmarkRequest;
use App\Http\Requests\Reader\StoreProgressRequest;
use App\Models\Book;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MemberLibraryController extends Controller
{
    public function favorite(Book $book, Request $request, BookAccessService $access): JsonResponse
    {
        $this->authorizeMemberBook($book, $request, $access);
        $request->user()->favorites()->firstOrCreate(['book_id' => $book->id]);

        return response()->json(['favorited' => true]);
    }

    public function unfavorite(Book $book, Request $request, BookAccessService $access): JsonResponse
    {
        $this->authorizeMemberBook($book, $request, $access);
        $request->user()->favorites()->where('book_id', $book->id)->delete();

        return response()->json(['favorited' => false]);
    }

    public function progress(Book $book, StoreProgressRequest $request, BookAccessService $access): JsonResponse
    {
        $this->authorizeMemberBook($book, $request, $access);
        $data = $request->validated();

        $history = DB::transaction(function () use ($book, $request, $data): ReadingHistory {
            $history = ReadingHistory::query()->lockForUpdate()->firstOrNew([
                'user_id' => $request->user()->id,
                'book_id' => $book->id,
            ]);
            $history->last_page = $data['page'];
            $history->duration_seconds = min(31536000, (int) $history->duration_seconds + $data['duration_delta']);
            $history->last_read_at = now();
            $history->save();

            return $history;
        });

        return response()->json(['progress' => $history->only('last_page', 'duration_seconds', 'last_read_at')]);
    }

    public function bookmark(Book $book, StoreBookmarkRequest $request, BookAccessService $access): JsonResponse
    {
        $this->authorizeMemberBook($book, $request, $access);
        $data = $request->validated();
        $bookmark = $request->user()->bookmarks()->updateOrCreate(
            ['book_id' => $book->id, 'page' => $data['page']],
            ['label' => $data['label'] ?? null, 'note' => $data['note'] ?? null],
        );

        return response()->json(['bookmark' => $bookmark->only('page', 'label', 'note')], Response::HTTP_CREATED);
    }

    public function destroyBookmark(Book $book, int $page, Request $request, BookAccessService $access): Response
    {
        $this->authorizeMemberBook($book, $request, $access);
        $request->user()->bookmarks()->where('book_id', $book->id)->where('page', $page)->delete();

        return response()->noContent();
    }

    private function authorizeMemberBook(Book $book, Request $request, BookAccessService $access): void
    {
        $user = $request->user();
        abort_unless(
            $user && $user->status === AccountStatus::Active
            && in_array($user->role, [UserRole::Member, UserRole::Superadmin], true),
            403,
        );
        $unlocked = $request->hasSession()
            ? (bool) $request->session()->get($access->passwordSessionKey($book), false)
            : false;
        abort_unless($access->canView($book, $user, $unlocked), 404);
    }
}
