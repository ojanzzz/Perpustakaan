<?php

namespace App\Http\Controllers\Analytics;

use App\Domain\Analytics\AnalyticsRecorder;
use App\Domain\Catalog\BookAccessService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreViewRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class ViewController extends Controller
{
    public function __invoke(Book $book, StoreViewRequest $request, BookAccessService $access, AnalyticsRecorder $analytics): JsonResponse
    {
        abort_unless($access->canView($book, $request->user()), 404);
        $data = $request->validated();
        $result = $analytics->recordView($book, $request, $data['session_key'], $data['page'], $data['duration_delta']);

        return response()->json(['recorded' => true], $result['created'] ? 201 : 200);
    }
}
