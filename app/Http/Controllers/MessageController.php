<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $messages = $user->messages()->latest()->get()
            ->map(fn($m) => [
                'kind'    => 'message',
                'item'    => $m,
                'date'    => $m->created_at,
                'unread'  => $m->isUnread(),
            ]);

        $readIds = $user->readAnnouncements()->pluck('announcements.id');
        $announcements = Announcement::with('newsArticle')->latest()->get()
            ->map(fn($a) => [
                'kind'   => 'announcement',
                'item'   => $a,
                'date'   => $a->created_at,
                'unread' => !$readIds->contains($a->id),
            ]);

        $inbox = $messages->merge($announcements)->sortByDesc('date')->values();

        return view('messages.index', compact('inbox'));
    }

    public function show(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        if ($message->isUnread()) {
            $message->update(['read_at' => now()]);
        }

        return view('messages.show', compact('message'));
    }

    public function showAnnouncement(Announcement $announcement)
    {
        $user = auth()->user();

        if (!$user->readAnnouncements()->where('announcement_id', $announcement->id)->exists()) {
            $user->readAnnouncements()->attach($announcement->id, ['created_at' => now()]);
        }

        return view('messages.show-announcement', compact('announcement'));
    }

    public function destroy(Message $message)
    {
        abort_if($message->user_id !== auth()->id(), 403);

        $message->delete();

        return redirect()->route('messages.index')->with('success', 'Message deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'No messages selected.');
        }

        Message::whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->delete();

        return back()->with('success', count($ids) . ' message(s) deleted.');
    }
}
