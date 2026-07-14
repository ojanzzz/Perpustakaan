<?php

use App\Http\Controllers\Analytics\ViewController;
use App\Http\Controllers\PublicPortal\SearchSuggestionController;
use App\Http\Controllers\Reader\MemberLibraryController;
use Illuminate\Support\Facades\Route;

Route::get('/search/suggestions', SearchSuggestionController::class)->middleware('throttle:60,1');
Route::post('/books/{book}/view', ViewController::class)->middleware(['web', 'throttle:120,1']);

Route::prefix('member/books/{book}')->middleware(['web', 'auth', 'throttle:120,1'])->group(function (): void {
    Route::put('/favorite', [MemberLibraryController::class, 'favorite']);
    Route::delete('/favorite', [MemberLibraryController::class, 'unfavorite']);
    Route::put('/progress', [MemberLibraryController::class, 'progress']);
    Route::post('/bookmarks', [MemberLibraryController::class, 'bookmark']);
    Route::delete('/bookmarks/{page}', [MemberLibraryController::class, 'destroyBookmark'])->whereNumber('page');
});
