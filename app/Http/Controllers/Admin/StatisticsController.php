<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\StatisticsExportService;
use App\Domain\Analytics\StatisticsReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StatisticsController extends Controller
{
    public function index(Request $request, StatisticsReport $statistics): View
    {
        $dates = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);

        return view('admin.statistics.index', ['report' => $statistics->build($dates['from'] ?? null, $dates['to'] ?? null)]);
    }

    public function export(Request $request, StatisticsReport $statistics, StatisticsExportService $exports): Response
    {
        $data = $request->validate([
            'format' => ['required', Rule::in(['csv', 'xlsx', 'pdf'])],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $rows = $statistics->rows($statistics->build($data['from'] ?? null, $data['to'] ?? null));

        return $exports->{$data['format']}($rows);
    }
}
