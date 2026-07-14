<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->where('status', 'active')->withCount('books')->orderBy('sort_order')->get();
        $subscribed = request()->user()->subscribedCategories()->pluck('categories.id')->all();

        return view('member.subscriptions', compact('categories', 'subscribed'));
    }

    public function toggle(Category $category, Request $request): RedirectResponse
    {
        $attached = $request->user()->subscribedCategories()->whereKey($category)->exists();
        $attached
            ? $request->user()->subscribedCategories()->detach($category)
            : $request->user()->subscribedCategories()->attach($category);

        return redirect()->route('member.subscriptions')->with('status', $attached ? 'Langganan dihentikan.' : 'Kategori berhasil dilanggani.');
    }
}
