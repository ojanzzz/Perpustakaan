<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\ProcessingStatus;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $visitChart = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = today()->subDays($daysAgo);

            return [
                'label' => $date->format('d M'),
                'value' => DB::table('book_views')->whereDate('viewed_at', $date)->count(),
            ];
        });

        return view('admin.dashboard', [
            'metrics' => [
                'books' => Book::count(),
                'drafts' => Book::where('status', BookStatus::Draft)->count(),
                'published' => Book::where('status', BookStatus::Published)->count(),
                'private' => Book::where('visibility', BookVisibility::Private)->count(),
                'users' => User::count(),
                'readers_today' => DB::table('book_views')->whereDate('viewed_at', today())->count(),
                'downloads' => DB::table('book_downloads')->count(),
                'failed' => Book::where('processing_status', ProcessingStatus::Failed)->count(),
            ],
            'latestBooks' => Book::query()->latest()->limit(6)->get(),
            'backup' => DB::table('backups')->latest()->first(),
            'visitChart' => $visitChart,
        ]);
    }
}
