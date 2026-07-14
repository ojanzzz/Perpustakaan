<?php

namespace App\Domain\Analytics;

use App\Models\Book;
use App\Models\BookDownload;
use App\Models\BookView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsRecorder
{
    /** @return array{view: BookView, created: bool} */
    public function recordView(Book $book, Request $request, string $sessionKey, int $page, int $durationDelta): array
    {
        $sessionHash = hash_hmac('sha256', $sessionKey, (string) config('app.key'));
        $visitorHash = $this->visitorHash($request);

        return DB::transaction(function () use ($book, $request, $sessionHash, $visitorHash, $page, $durationDelta): array {
            $query = BookView::query()->lockForUpdate()->where('book_id', $book->id)
                ->whereDate('viewed_at', today());
            $request->user()
                ? $query->where('user_id', $request->user()->id)
                : $query->whereNull('user_id')->where('session_hash', $sessionHash);

            $view = $query->first();
            $created = $view === null;
            $view ??= new BookView(['book_id' => $book->id, 'viewed_at' => now()]);
            $view->fill([
                'user_id' => $request->user()?->id,
                'visitor_hash' => $visitorHash,
                'session_hash' => $sessionHash,
                'device_type' => $this->device($request->userAgent()),
                'browser' => $this->browser($request->userAgent()),
                'referrer' => $this->safeReferrer($request->headers->get('referer')),
                'last_page' => $page,
                'duration_seconds' => min(31536000, (int) $view->duration_seconds + $durationDelta),
            ]);
            $view->save();

            return ['view' => $view, 'created' => $created];
        });
    }

    public function recordDownload(Book $book, Request $request): BookDownload
    {
        return BookDownload::query()->create([
            'book_id' => $book->id,
            'user_id' => $request->user()?->id,
            'visitor_hash' => $this->visitorHash($request),
            'device_type' => $this->device($request->userAgent()),
            'downloaded_at' => now(),
        ]);
    }

    private function visitorHash(Request $request): string
    {
        return hash_hmac('sha256', ($request->ip() ?: 'unknown').'|'.today()->toDateString(), (string) config('app.key'));
    }

    private function device(?string $agent): string
    {
        $agent = strtolower($agent ?? '');
        if (preg_match('/ipad|tablet/', $agent)) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|android/', $agent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browser(?string $agent): string
    {
        $agent ??= '';

        return match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Lainnya',
        };
    }

    private function safeReferrer(?string $referrer): ?string
    {
        if (! $referrer) {
            return null;
        }
        $parts = parse_url($referrer);
        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        return ($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '/');
    }
}
