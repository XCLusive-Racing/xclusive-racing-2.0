<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NewsTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsArticle::with(['author', 'tags'])
            ->published()
            ->latest('published_at');

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
            );
        }

        $articles    = $query->paginate(9)->withQueryString();
        $tags        = NewsTag::orderBy('name')->get();
        $activeTag   = $request->tag;

        return view('news.index', compact('articles', 'tags', 'activeTag'));
    }

    public function show(string $slug): View
    {
        $article = NewsArticle::with(['author', 'tags'])
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('news.show', compact('article'));
    }
}
