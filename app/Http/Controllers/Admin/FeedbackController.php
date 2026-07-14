<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditRecorder;
use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['new', 'in_progress', 'resolved', 'closed'])]])['status'] ?? null;
        $feedback = Feedback::query()->with(['book:id,title,slug', 'user:id,name,email', 'resolver:id,name'])
            ->when($status, fn ($query) => $query->where('status', $status))->latest()->paginate(20)->withQueryString();

        return view('admin.feedback.index', compact('feedback', 'status'));
    }

    public function update(Feedback $feedback, Request $request, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'resolved', 'closed'])],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $before = $feedback->getAttributes();
        $resolved = in_array($data['status'], ['resolved', 'closed'], true);
        $feedback->update([...$data, 'resolved_by' => $resolved ? $request->user()->id : null, 'resolved_at' => $resolved ? now() : null]);
        $audit->record('feedback.update', $feedback, $before, $feedback->getChanges());

        return redirect()->route('admin.feedback.index')->with('status', 'Status pesan diperbarui.');
    }
}
