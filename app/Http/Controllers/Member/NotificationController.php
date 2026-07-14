<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('member.notifications', ['notifications' => request()->user()->notifications()->paginate(20)]);
    }

    public function read(DatabaseNotification $notification, Request $request): RedirectResponse
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id && $notification->notifiable_type === $request->user()::class, 404);
        $notification->markAsRead();

        return redirect()->route('member.notifications');
    }
}
