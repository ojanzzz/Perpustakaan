<?php

namespace Database\Seeders;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\ProcessingStatus;
use App\Enums\UserRole;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Language;
use App\Models\Publisher;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $demoPdf = database_path('seeders/assets/demo-reader.pdf');
        $demoPdfPath = 'demo/demo-reader.pdf';
        Storage::disk('private')->put($demoPdfPath, file_get_contents($demoPdf));
        $demoPdfSize = filesize($demoPdf);
        $demoPdfHash = hash_file('sha256', $demoPdf);

        $creator = User::query()
            ->where('role', UserRole::Superadmin)
            ->where('email', 'superadmin@demo.test')
            ->firstOrFail();
        $publisher = Publisher::withTrashed()->updateOrCreate(['slug' => 'penerbit-demo-kpu'], ['name' => 'Penerbit Demo KPU']);
        $publisher->restore();
        $language = Language::query()->updateOrCreate(['code' => 'id'], ['name' => 'Bahasa Indonesia', 'is_active' => true]);
        $author = Author::withTrashed()->updateOrCreate(['slug' => 'tim-literasi-demo'], ['name' => 'Tim Literasi Demo']);
        $author->restore();
        $tag = Tag::query()->updateOrCreate(['slug' => 'dokumen-demo'], ['name' => 'Dokumen Demo']);

        $categoryNames = ['Pendidikan Pemilih', 'Sejarah Pemilu', 'Regulasi', 'Pedoman Teknis', 'Laporan Kegiatan', 'Majalah', 'Buletin', 'Data Pemilu', 'Kelembagaan', 'Publikasi Tahunan'];
        $categories = collect($categoryNames)->map(function (string $name, int $index): Category {
            $category = Category::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => "Kategori contoh {$name}.", 'sort_order' => $index + 1, 'status' => 'active']
            );
            $category->restore();

            return $category;
        });

        $collectionNames = ['Rak Utama', 'Koleksi Terbaru', 'Referensi Pemilu', 'Literasi Demokrasi', 'Arsip Tahunan'];
        $collections = collect($collectionNames)->map(function (string $name, int $index): Collection {
            $collection = Collection::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => "Rak contoh {$name}.", 'visibility' => 'public', 'sort_order' => $index + 1, 'status' => 'active']
            );
            $collection->restore();

            return $collection;
        });

        $titles = [
            'Mengenal Tahapan Pemilihan', 'Panduan Partisipasi Warga', 'Dasar-Dasar Literasi Demokrasi',
            'Kelembagaan Penyelenggara Pemilu', 'Data Pemilu untuk Publik', 'Pendidikan Pemilih Inklusif',
            'Etika Pelayanan Kepemiluan', 'Arsip Demokrasi Indonesia', 'Pengantar Regulasi Pemilihan',
            'Komunikasi Publik dan Pemilu', 'Panduan Informasi Kepemiluan', 'Perempuan dan Partisipasi Politik',
            'Pemilih Muda dan Ruang Digital', 'Aksesibilitas dalam Pemilihan', 'Transparansi Data Pemilu',
            'Sejarah Penyelenggaraan Pemilu', 'Pengawasan Partisipatif', 'Manajemen Dokumen Publik',
            'Kamus Istilah Kepemiluan', 'Catatan Literasi Demokrasi',
        ];
        $covers = ['civic-red.webp', 'participation-navy.webp', 'election-data.webp', 'archive-gold.webp'];
        $types = ['Panduan', 'Modul', 'Laporan', 'Referensi'];

        foreach ($titles as $index => $title) {
            $number = $index + 1;
            $book = Book::withTrashed()->updateOrCreate(['slug' => sprintf('dokumen-publik-domain-demo-%02d', $number)], [
                'title' => $title,
                'subtitle' => $number % 3 === 0 ? 'Bahan pengantar untuk pembaca dan masyarakat' : null,
                'description' => 'Publikasi demo original untuk pengembangan E-Perpustakaan Digital KPU. Materi ini berisi metadata contoh mengenai literasi demokrasi dan tidak mengambil isi publikasi berhak cipta.',
                'publisher_id' => $publisher->id,
                'language_id' => $language->id,
                'publication_year' => 2026 - ($number % 6),
                'publication_date' => now()->subDays($number)->toDateString(),
                'publication_type' => $types[$index % count($types)],
                'document_number' => sprintf('DEMO/KPU/%02d/2026', $number),
                'page_count' => 12,
                'file_size' => $demoPdfSize,
                'original_file' => $demoPdfPath,
                'optimized_file' => $demoPdfPath,
                'file_hash' => $demoPdfHash,
                'cover_image' => 'images/demo-covers/'.$covers[$index % count($covers)],
                'processing_status' => ProcessingStatus::Completed,
                'status' => BookStatus::Published,
                'visibility' => BookVisibility::Public,
                'download_enabled' => $number % 3 !== 0,
                'print_enabled' => $number % 4 === 0,
                'published_at' => now()->subDays($number),
                'sort_order' => $number,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);
            $book->restore();
            $book->categories()->sync([$categories[($number - 1) % $categories->count()]->id]);
            $book->collections()->sync([$collections[($number - 1) % $collections->count()]->id => ['sort_order' => $number]]);
            $book->authors()->sync([$author->id]);
            $book->tags()->sync([$tag->id]);

            DB::table('book_views')->where('book_id', $book->id)->delete();
            foreach (range(1, ($number % 5) + 1) as $view) {
                DB::table('book_views')->insert([
                    'book_id' => $book->id,
                    'visitor_hash' => hash('sha256', "demo-visitor-{$number}-{$view}"),
                    'session_hash' => hash('sha256', "demo-session-{$number}-{$view}"),
                    'device_type' => $view % 2 ? 'desktop' : 'mobile',
                    'duration_seconds' => 90 + ($view * 30),
                    'last_page' => min(5 + $view, 32 + ($number * 4)),
                    'viewed_at' => now()->subDays($view - 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('announcements')->updateOrInsert(['title' => 'Selamat Datang di E-Perpustakaan'], [
            'content' => 'Pengumuman contoh untuk environment development.',
            'is_active' => true,
            'deleted_at' => null,
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
