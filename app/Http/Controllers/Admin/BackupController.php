<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunBackup;
use App\Jobs\RunRestore;
use App\Models\Backup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): View
    {
        return view('admin.backups.index', ['backups' => Backup::query()->with('requester:id,name')->latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', Rule::in(['database'])]]);
        $backup = Backup::query()->create([...$data, 'disk' => 'backups', 'status' => 'pending', 'requested_by' => $request->user()->id]);
        RunBackup::dispatch($backup->id);

        return redirect()->route('admin.backups.index')->with('status', 'Backup masuk antrean pemrosesan.');
    }

    public function download(Backup $backup): BinaryFileResponse
    {
        abort_unless($backup->status === 'completed' && $backup->path && Storage::disk($backup->disk)->exists($backup->path), 404);

        return response()->download(Storage::disk($backup->disk)->path($backup->path), basename($backup->path));
    }

    public function restore(Backup $backup, Request $request): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:PULIHKAN']]);
        abort_unless($backup->status === 'completed', 422);
        RunRestore::dispatch($backup->id);

        return redirect()->route('admin.backups.index')->with('status', 'Pemulihan masuk antrean. Sistem membuat backup pra-restore terlebih dahulu.');
    }
}
