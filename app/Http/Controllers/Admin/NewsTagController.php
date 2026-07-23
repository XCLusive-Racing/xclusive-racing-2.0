<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewsTagController extends Controller
{
    public function index(): View
    {
        $tags = NewsTag::withCount('articles')->orderBy('name')->get();

        return view('admin.news.tags.index', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:50|unique:news_tags,name',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $data['slug'] = Str::slug($data['name']);

        NewsTag::create($data);

        return redirect()->route('admin.news.tags.index')
            ->with('success', 'Tag "' . $data['name'] . '" created.');
    }

    public function destroy(NewsTag $newsTag): RedirectResponse
    {
        $newsTag->articles()->detach();
        $newsTag->delete();

        return redirect()->route('admin.news.tags.index')
            ->with('success', 'Tag deleted.');
    }
}
