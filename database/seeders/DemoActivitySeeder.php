<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\PersonalCollection;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoActivitySeeder extends Seeder
{
    private const DEMO_CATEGORY_SLUGS = [
        'pendidikan-pemilih', 'sejarah-pemilu', 'regulasi', 'pedoman-teknis',
        'laporan-kegiatan', 'majalah', 'buletin', 'data-pemilu', 'kelembagaan',
        'publikasi-tahunan',
    ];

    public function run(): void
    {
        $member = User::query()
            ->where('role', UserRole::Member)
            ->where('email', 'member@demo.test')
            ->firstOrFail();
        $superadmin = User::query()
            ->where('role', UserRole::Superadmin)
            ->where('email', 'superadmin@demo.test')
            ->firstOrFail();
        $books = Book::query()
            ->where('slug', 'like', 'dokumen-publik-domain-demo-%')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $books->take(3)->each(fn (Book $book) => Favorite::query()->updateOrCreate([
            'user_id' => $member->id,
            'book_id' => $book->id,
        ]));

        foreach ([7, 5, 9, 3] as $index => $lastPage) {
            $book = $books[$index];
            ReadingHistory::query()->updateOrCreate(
                ['user_id' => $member->id, 'book_id' => $book->id],
                [
                    'last_page' => $lastPage,
                    'duration_seconds' => 420 + ($index * 180),
                    'last_read_at' => now()->subHours($index + 1),
                ]
            );
        }

        foreach ([2, 4, 7] as $index => $page) {
            Bookmark::query()->updateOrCreate(
                ['user_id' => $member->id, 'book_id' => $books[$index]->id, 'page' => $page],
                [
                    'label' => ['Tahapan penting', 'Catatan partisipasi', 'Data utama'][$index],
                    'note' => 'Bookmark contoh untuk pengujian fitur anggota.',
                ]
            );
        }

        $personalCollection = PersonalCollection::withTrashed()->firstOrNew([
            'user_id' => $member->id,
            'slug' => 'bacaan-pilihan',
        ]);
        $personalCollection->fill([
            'name' => 'Bacaan Pilihan',
            'description' => 'Koleksi pribadi contoh untuk akun anggota demo.',
        ]);
        $personalCollection->save();
        $personalCollection->restore();
        $personalCollection->books()->sync(
            $books->take(4)->mapWithKeys(fn (Book $book, int $index) => [
                $book->id => ['sort_order' => $index + 1],
            ])->all()
        );

        Category::query()->whereIn('slug', self::DEMO_CATEGORY_SLUGS)->orderBy('sort_order')->orderBy('id')->take(2)->get()->each(
            fn (Category $category) => DB::table('category_subscriptions')->updateOrInsert(
                ['user_id' => $member->id, 'category_id' => $category->id],
                ['created_at' => now(), 'updated_at' => now()]
            )
        );

        $this->seedDownloads($books, $member);
        $this->seedSearches();
        $this->seedFeedback($books, $member);
        $this->seedAuditLogs($books, $superadmin);
    }

    private function seedDownloads($books, User $member): void
    {
        $books->where('download_enabled', true)->take(8)->values()->each(
            function (Book $book, int $index) use ($member): void {
                DB::table('book_downloads')->updateOrInsert(
                    [
                        'book_id' => $book->id,
                        'visitor_hash' => hash('sha256', "demo-download-{$book->id}"),
                    ],
                    [
                        'user_id' => $index % 2 === 0 ? $member->id : null,
                        'device_type' => $index % 3 === 0 ? 'mobile' : 'desktop',
                        'downloaded_at' => now()->subDays($index)->subMinutes(15),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        );
    }

    private function seedSearches(): void
    {
        $searches = [
            ['pendidikan pemilih', 4, ['category' => 'Pendidikan Pemilih']],
            ['data pemilu', 3, []],
            ['literasi demokrasi', 5, []],
            ['partisipasi warga', 2, []],
            ['regulasi pemilihan', 2, ['year_from' => 2022]],
            ['pemilih muda', 1, []],
            ['jadwal pemilu luar negeri', 0, []],
            ['dokumen tahun 1990', 0, ['year_to' => 1990]],
        ];

        foreach ($searches as $index => [$query, $resultCount, $filters]) {
            DB::table('search_logs')->updateOrInsert(
                ['session_hash' => hash('sha256', "demo-search-{$index}")],
                [
                    'query' => $query,
                    'normalized_query' => $query,
                    'result_count' => $resultCount,
                    'filters' => $filters === [] ? null : json_encode($filters, JSON_THROW_ON_ERROR),
                    'created_at' => now()->subDays($index % 4)->subMinutes($index * 7),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedFeedback($books, User $member): void
    {
        DB::table('feedback')->updateOrInsert(
            ['subject' => 'Saran kategori literasi pemilih pemula'],
            [
                'user_id' => $member->id,
                'book_id' => null,
                'type' => 'suggestion',
                'name' => $member->name,
                'email' => $member->email,
                'message' => 'Mohon tambahkan lebih banyak bahan bacaan untuk pemilih pemula.',
                'status' => 'new',
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ]
        );

        DB::table('feedback')->updateOrInsert(
            ['subject' => 'Contoh laporan dokumen'],
            [
                'user_id' => null,
                'book_id' => $books->first()->id,
                'type' => 'document_report',
                'name' => 'Pengunjung Demo',
                'email' => null,
                'message' => 'Laporan contoh untuk menguji alur penanganan dokumen bermasalah.',
                'status' => 'new',
                'created_at' => now()->subHours(6),
                'updated_at' => now(),
            ]
        );
    }

    private function seedAuditLogs($books, User $superadmin): void
    {
        $logs = [
            ['auth.login', User::class, $superadmin->id, null, ['status' => 'success']],
            ['books.create', Book::class, $books[0]->id, null, ['title' => $books[0]->title]],
            ['books.publish', Book::class, $books[1]->id, ['status' => 'draft'], ['status' => 'published']],
            ['permissions.update', User::class, $superadmin->id, null, ['role' => UserRole::Superadmin->value]],
        ];

        foreach ($logs as $index => [$action, $targetType, $targetId, $before, $after]) {
            $exists = DB::table('audit_logs')
                ->where('action', $action)
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->exists();

            if (! $exists) {
                DB::table('audit_logs')->insert([
                    'user_id' => $superadmin->id,
                    'action' => $action,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'before_values' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
                    'after_values' => json_encode($after, JSON_THROW_ON_ERROR),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Demo Seeder',
                    'created_at' => now()->subHours($index + 1),
                ]);
            }
        }
    }
}
