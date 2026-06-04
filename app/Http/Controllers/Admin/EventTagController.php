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
            return back()->withErrors(['name' => 'A tag with this name already exists.']);
        }

        EventTag::create($data);

        return back()->withInput()->with('tag_success', 'Tag "' . $data['name'] . '" added.');
    }

    public function destroy(EventTag $eventTag)
    {
        $eventTag->delete();
        return back()->with('success', 'Tag deleted.');
    }
}