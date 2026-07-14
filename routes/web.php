<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\BookWorkflowController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\EmbedController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\Member\LibraryController;
use App\Http\Controllers\Member\NotificationController;
use App\Http\Controllers\Member\PersonalCollectionController;
use App\Http\Controllers\Member\ProfileController;
use App\Http\Controllers\Member\SubscriptionController;
use App\Http\Controllers\PublicPortal\BookController as PublicBookController;
use App\Http\Controllers\PublicPortal\CatalogController as PublicCatalogController;
use App\Http\Controllers\PublicPortal\CategoryController as PublicCategoryController;
use App\Http\Controllers\PublicPortal\CollectionController as PublicCollectionController;
use App\Http\Controllers\PublicPortal\HomeController;
use App\Http\Controllers\PublicPortal\MachineController;
use App\Http\Controllers\Reader\DocumentController;
use App\Http\Controllers\Reader\ReaderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/manifest.webmanifest', fn () => response(file_get_contents(public_path('manifest.webmanifest')), 200, ['Content-Type' => 'application/manifest+json']));
Route::get('/service-worker.js', fn () => response(file_get_contents(public_path('service-worker.js')), 200, ['Content-Type' => 'application/javascript']));
Route::get('/katalog', [PublicCatalogController::class, 'index'])->name('catalog.index');
Route::get('/cari', [PublicCatalogController::class, 'index'])->name('search.index');
Route::get('/terbaru', [PublicCatalogController::class, 'latest'])->name('catalog.latest');
Route::get('/terpopuler', [PublicCatalogController::class, 'popular'])->name('catalog.popular');
Route::get('/rak/{collection:slug}', PublicCollectionController::class)->name('collections.show');
Route::get('/kategori/{category:slug}', PublicCategoryController::class)->name('categories.show');
Route::get('/buku/{book:slug}', [PublicBookController::class, 'show'])->name('books.show');
Route::post('/buku/{book:slug}/akses', [PublicBookController::class, 'unlock'])
    ->middleware('throttle:5,1')->name('books.unlock');
Route::get('/buku/{book:slug}/baca', ReaderController::class)->name('reader.show');
Route::get('/buku/{book:slug}/dokumen', [DocumentController::class, 'show'])
    ->middleware(['signed', 'throttle:120,1'])->name('reader.document');
Route::get('/buku/{book:slug}/unduh', [DocumentController::class, 'download'])
    ->middleware(['signed', 'throttle:20,1'])->name('reader.download');
Route::view('/tentang', 'public.static.about')->name('about');
Route::view('/panduan', 'public.static.guide')->name('guide');
Route::view('/kontak', 'public.static.contact')->name('contact');
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:5,1')->name('feedback.store');
Route::view('/privasi', 'public.static.privacy')->name('privacy');
Route::get('/sitemap.xml', [MachineController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [MachineController::class, 'robots'])->name('robots');

Route::prefix('embed')->middleware('embed.domain')->group(function (): void {
    Route::get('/buku/{book:slug}', [EmbedController::class, 'book'])->name('embed.book');
    Route::get('/rak/{collection:slug}', [EmbedController::class, 'collection'])->name('embed.collection');
    Route::get('/kategori/{category:slug}', [EmbedController::class, 'category'])->name('embed.category');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('/two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('/two-factor/setup', [TwoFactorController::class, 'enable'])->middleware('throttle:6,1')->name('two-factor.enable');
    Route::get('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor/challenge', [TwoFactorController::class, 'confirm'])->middleware('throttle:6,1')->name('two-factor.confirm');
    Route::delete('/two-factor', [TwoFactorController::class, 'disable'])->middleware('throttle:3,1')->name('two-factor.disable');
});

Route::middleware(['auth', 'member'])->group(function (): void {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('member.profile');
    Route::put('/profil', [ProfileController::class, 'update'])->name('member.profile.update');
    Route::put('/profil/password', [ProfileController::class, 'password'])->name('member.password.update');
    Route::delete('/akun', [ProfileController::class, 'destroy'])->name('member.account.destroy');
    Route::get('/favorit', [LibraryController::class, 'favorites'])->name('member.favorites');
    Route::get('/riwayat-baca', [LibraryController::class, 'history'])->name('member.history');
    Route::get('/bookmark', [LibraryController::class, 'bookmarks'])->name('member.bookmarks');
    Route::get('/koleksi-saya', [PersonalCollectionController::class, 'index'])->name('member.collections');
    Route::post('/koleksi-saya', [PersonalCollectionController::class, 'store'])->name('member.collections.store');
    Route::post('/koleksi-saya/{personalCollection}/buku', [PersonalCollectionController::class, 'addBook'])->name('member.collections.books.store');
    Route::delete('/koleksi-saya/{personalCollection}/buku/{book}', [PersonalCollectionController::class, 'removeBook'])->name('member.collections.books.destroy');
    Route::delete('/koleksi-saya/{personalCollection}', [PersonalCollectionController::class, 'destroy'])->name('member.collections.destroy');
    Route::get('/langganan', [SubscriptionController::class, 'index'])->name('member.subscriptions');
    Route::put('/langganan/{category}', [SubscriptionController::class, 'toggle'])->name('member.subscriptions.toggle');
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('member.notifications');
    Route::put('/notifikasi/{notification}/baca', [NotificationController::class, 'read'])->name('member.notifications.read');
});

Route::prefix('admin')->middleware(['auth', 'admin.2fa'])->group(function (): void {
    Route::get('/', DashboardController::class)
        ->middleware('permission:dashboard.view')->name('admin.dashboard');
    Route::get('/books', [BookController::class, 'index'])
        ->middleware('permission:books.view')->name('admin.books.index');
    Route::get('/books/create', [BookController::class, 'create'])
        ->middleware('permission:books.create')->name('admin.books.create');
    Route::post('/books', [BookController::class, 'store'])
        ->middleware('permission:books.create')->name('admin.books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->middleware('permission:books.update')->name('admin.books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->middleware('permission:books.update')->name('admin.books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->middleware('permission:books.delete')->name('admin.books.destroy');
    Route::delete('/books/{book}/force', [BookController::class, 'forceDelete'])->middleware('permission:books.force_delete')->name('admin.books.force_delete');
    Route::post('/books/{book}/submit', [BookWorkflowController::class, 'submit'])->middleware('permission:books.submit')->name('admin.books.submit');
    Route::post('/books/{book}/return', [BookWorkflowController::class, 'return'])->middleware('permission:books.review')->name('admin.books.return');
    Route::post('/books/{book}/publish', [BookWorkflowController::class, 'publish'])->middleware('permission:books.publish')->name('admin.books.publish');
    Route::post('/books/{book}/archive', [BookWorkflowController::class, 'archive'])->middleware('permission:books.archive')->name('admin.books.archive');

    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:books.view')->name('admin.categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:taxonomy.manage')->name('admin.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:taxonomy.manage')->name('admin.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:taxonomy.manage')->name('admin.categories.destroy');

    Route::get('/collections', [CollectionController::class, 'index'])->middleware('permission:books.view')->name('admin.collections.index');
    Route::post('/collections', [CollectionController::class, 'store'])->middleware('permission:taxonomy.manage')->name('admin.collections.store');
    Route::put('/collections/{collection}', [CollectionController::class, 'update'])->middleware('permission:taxonomy.manage')->name('admin.collections.update');
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy'])->middleware('permission:taxonomy.manage')->name('admin.collections.destroy');
    Route::get('/statistics', [StatisticsController::class, 'index'])->middleware('permission:analytics.view')->name('admin.statistics.index');
    Route::get('/statistics/export', [StatisticsController::class, 'export'])->middleware('permission:analytics.export')->name('admin.statistics.export');
    Route::get('/feedback', [AdminFeedbackController::class, 'index'])->middleware('permission:feedback.manage')->name('admin.feedback.index');
    Route::put('/feedback/{feedback}', [AdminFeedbackController::class, 'update'])->middleware('permission:feedback.manage')->name('admin.feedback.update');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('admin.audit.index');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->middleware('permission:audit.view')->name('admin.audit.export');
    Route::get('/backups', [BackupController::class, 'index'])->middleware('permission:backup.view')->name('admin.backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->middleware('permission:backup.run')->name('admin.backups.store');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->middleware('permission:backup.view')->name('admin.backups.download');
    Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->middleware('permission:backup.restore')->name('admin.backups.restore');
    Route::get('/settings', [SettingController::class, 'edit'])->middleware('permission:settings.manage')->name('admin.settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('permission:settings.manage')->name('admin.settings.update');
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.manage_members')->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.manage_admins')->name('admin.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.manage_members')->name('admin.users.update');
    Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])->middleware('permission:permissions.manage')->name('admin.users.permissions');
    Route::put('/users/{user}/permissions', [UserController::class, 'updatePermissions'])->middleware('permission:permissions.manage')->name('admin.users.permissions.update');
});
