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

        $messages = $user->messages()->latest()->get()->toBase()
            ->map(fn($m) => [
                'kind'    => 'message',
                'item'    => $m,
                'date'    => $m->created_at,
                'unread'  => $m->isUnread(),
            ]);

        $readIds      = $user->readAnnouncements()->pluck('announcements.id');
        $dismissedIds = $user->dismissedAnnouncementIds();
        $announcements = Announcement::with('newsArticle')
            ->whereNotIn('id', $dismissedIds)
            ->latest()->get()->toBase()
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

    public function destroyAnnouncement(Announcement $announcement)
    {
        auth()->user()->dismissAnnouncement($announcement->id);

        return redirect()->route('messages.index')->with('success', 'Removed from inbox.');
    }

    public function bulkDestroy(Request $request)
    {
        $messageIds      = $request->input('message_ids', []);
        $announcementIds = $request->input('announcement_ids', []);

        if (empty($messageIds) && empty($announcementIds)) {
            return back()->with('error', 'No items selected.');
        }

        $user = auth()->user();

        if (!empty($messageIds)) {
            Message::whereIn('id', $messageIds)
                ->where('user_id', $user->id)
                ->delete();
        }

        foreach ($announcementIds as $announcementId) {
            $user->dismissAnnouncement((int) $announcementId);
        }

        $count = count($messageIds) + count($announcementIds);
        return back()->with('success', $count . ' item(s) removed.');
    }
}
