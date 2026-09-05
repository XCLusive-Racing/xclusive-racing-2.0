<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NewsTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $tags      = NewsTag::orderBy('name')->get();
        $activeTag = $request->tag;

        // Featured = most recent published article (no filters applied)
        $featured = NewsArticle::with(['author', 'tags'])
            ->published()
            ->latest('published_at')
            ->first();

        $query = NewsArticle::with(['author', 'tags'])
            ->published()
            ->latest('published_at');

        // Exclude featured from grid
        if ($featured) {
            $query->where('id', '!=', $featured->id);
        }

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

        $articles = $query->paginate(9)->withQueryString();

        return view('news.index', compact('articles', 'tags', 'activeTag', 'featured'));
    }

    public function show(string $slug): View
    {
        $article = NewsArticle::with(['author', 'tags'])
            ->withCount('likedByUsers')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        // One view per browser session per article, so refreshing/re-reading doesn't inflate the count.
        $viewed = session('viewed_news_articles', []);
        if (!in_array($article->id, $viewed, true)) {
            $article->increment('views_count');
            session(['viewed_news_articles' => [...$viewed, $article->id]]);
        }

        $liked = $article->isLikedBy(auth()->user());

        return view('news.show', compact('article', 'liked'));
    }

    public function toggleLike(string $slug): JsonResponse
    {
        $article = NewsArticle::published()->where('slug', $slug)->firstOrFail();
        $user    = auth()->user();

        if ($article->isLikedBy($user)) {
            $article->likedByUsers()->detach($user->id);
            $liked = false;
        } else {
            $article->likedByUsers()->syncWithoutDetaching([$user->id]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'count' => $article->likedByUsers()->count(),
        ]);
    }
}
