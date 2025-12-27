<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class NoticeBoardController extends Controller
{
    public function index(): Response
    {
        $notices = Notice::orderBy('created_at', 'desc')
            ->get()
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'type' => $n->type,
                'is_active' => $n->is_active,
                'created_at' => $n->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('NoticeBoard/Index', [
            'notices' => $notices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'is_active' => 'boolean',
        ]);

        Notice::create($validated);

        return redirect()->back()->with('success', 'Notice created successfully.');
    }

    public function update(Request $request, Notice $notice): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,success,danger',
            'is_active' => 'boolean',
        ]);

        $notice->update($validated);

        return redirect()->back()->with('success', 'Notice updated successfully.');
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return redirect()->back()->with('success', 'Notice deleted successfully.');
    }
}
