<?php

namespace App\Domain\Analytics;

use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatisticsReport
{
    /** @return array<string, mixed> */
    public function build(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? now()->parse($from)->startOfDay() : now()->subDays(29)->startOfDay();
        $toDate = $to ? now()->parse($to)->endOfDay() : now()->endOfDay();

        $views = DB::table('book_views')->whereBetween('viewed_at', [$fromDate, $toDate]);
        $downloads = DB::table('book_downloads')->whereBetween('downloaded_at', [$fromDate, $toDate]);
        $dailyRows = (clone $views)->selectRaw('DATE(viewed_at) as day, COUNT(*) as total, SUM(duration_seconds) as duration')
            ->groupByRaw('DATE(viewed_at)')->orderBy('day')->get()->keyBy('day');
        $daily = collect();
        for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
            $row = $dailyRows->get($date->toDateString());
            $daily->push(['label' => $date->format('d M'), 'views' => (int) ($row->total ?? 0), 'duration' => (int) ($row->duration ?? 0)]);
        }

        $popularBooks = Book::query()->select(['books.id', 'books.title', 'books.slug'])
            ->withCount(['views' => fn ($query) => $query->whereBetween('viewed_at', [$fromDate, $toDate]),
                'downloads' => fn ($query) => $query->whereBetween('downloaded_at', [$fromDate, $toDate])])
            ->orderByDesc('views_count')->orderByDesc('downloads_count')->limit(10)->get();

        return [
            'from' => $fromDate, 'to' => $toDate,
            'metrics' => [
                'views' => (clone $views)->count(),
                'unique_views' => (clone $views)->distinct()->count('visitor_hash'),
                'downloads' => (clone $downloads)->count(),
                'duration_seconds' => (int) (clone $views)->sum('duration_seconds'),
                'searches' => DB::table('search_logs')->whereBetween('created_at', [$fromDate, $toDate])->count(),
                'no_results' => DB::table('search_logs')->whereBetween('created_at', [$fromDate, $toDate])->where('result_count', 0)->count(),
            ],
            'daily' => $daily,
            'popularBooks' => $popularBooks,
            'devices' => (clone $views)->select('device_type', DB::raw('COUNT(*) as total'))->groupBy('device_type')->pluck('total', 'device_type'),
            'searchTerms' => DB::table('search_logs')->select('normalized_query', DB::raw('COUNT(*) as total'))->whereBetween('created_at', [$fromDate, $toDate])->groupBy('normalized_query')->orderByDesc('total')->limit(10)->get(),
        ];
    }

    /** @param array<string, mixed> $report */
    public function rows(array $report): Collection
    {
        return $report['popularBooks']->map(fn (Book $book) => [
            $book->title, (int) $book->views_count, (int) $book->downloads_count,
        ])->prepend(['Judul buku', 'Dibaca', 'Diunduh']);
    }
}
