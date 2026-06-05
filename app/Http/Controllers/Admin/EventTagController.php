<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventTagController extends Controller
{
    public function index()
    {
        $tags = EventTag::orderBy('name')->get();
        return view('admin.event-tags.index', compact('tags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $data['slug'] = Str::slug($data['name']);

        if (EventTag::where('slug', $data['slug'])->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => ['name' => ['A tag with this name already exists.']]], 422);
            }
            return back()->withErrors(['name' => 'A tag with this name already exists.']);
        }

        $tag = EventTag::create($data);

        if ($request->expectsJson()) {
            return response()->json($tag);
        }

        return back()->withInput()->with('tag_success', 'Tag "' . $data['name'] . '" added.');
    }

    public function destroy(EventTag $eventTag)
    {
        $eventTag->delete();

        if (request()->expectsJson() || request()->header('X-HTTP-Method-Override') === 'DELETE') {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Tag deleted.');
    }
}