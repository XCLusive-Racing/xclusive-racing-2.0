<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\NewsArticle;
use App\Models\NewsTag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsArticleController extends Controller
{
    public function index(Request $request): View
    {
        $user  = auth()->user();
        $query = NewsArticle::with(['author', 'tags'])->latest();

        if ($user->isBroadcaster() && !$user->isAdmin() && !$user->isOwner()) {
            $query->where('author_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        if ($request->filled('author') && ($user->isAdmin() || $user->isOwner())) {
            $query->where('author_id', $request->author);
        }

        $articles = $query->paginate(25)->withQueryString();
        $tags     = NewsTag::orderBy('name')->get();
        $authors  = ($user->isAdmin() || $user->isOwner())
            ? User::whereHas('roles', fn($q) => $q->whereIn('slug', ['owner', 'admin', 'broadcaster']))->orderBy('name')->get()
            : collect();

        return view('admin.news.index', compact('articles', 'tags', 'authors'));
    }

    public function create(): View
    {
        $tags    = NewsTag::orderBy('name')->get();
        $authors = $this->authorOptions();

        return view('admin.news.create', compact('tags', 'authors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:news_articles,slug',
            'excerpt'      => 'nullable|string|max:200',
            'body'         => 'nullable|string',
            'cover_image'  => 'nullable|image|max:10240',
            'status'       => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'tags'         => 'nullable|array',
            'tags.*'       => 'integer|exists:news_tags,id',
            'author_id'    => 'nullable|integer|exists:users,id',
        ]);

        $data['cover_image'] = $this->resolveImage($request);

        $data['author_id']    = ($user->isAdmin() || $user->isOwner()) && !empty($data['author_id'])
            ? $data['author_id']
            : $user->id;

        $data['published_at'] = $data['status'] === 'published' && empty($data['published_at'])
            ? now()
            : ($data['published_at'] ?? null);

        $data['slug'] = !empty($data['slug'])
            ? $data['slug']
            : NewsArticle::uniqueSlug($data['title']);

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $article = NewsArticle::create($data);
        $article->tags()->sync($tags);

        return redirect()->route('admin.news.index')
            ->with('success', 'Article "' . $article->title . '" created.');
    }

    public function edit(NewsArticle $newsArticle): View
    {
        $this->authorizeArticle($newsArticle);

        $tags    = NewsTag::orderBy('name')->get();
        $authors = $this->authorOptions();

        return view('admin.news.edit', compact('newsArticle', 'tags', 'authors'));
    }

    public function update(Request $request, NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorizeArticle($newsArticle);

        $user = auth()->user();
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:news_articles,slug,' . $newsArticle->id,
            'excerpt'      => 'nullable|string|max:200',
            'body'         => 'nullable|string',
            'cover_image'  => 'nullable|image|max:10240',
            'status'       => 'required|in:draft,published',
            'published_at' => 'nullable|date',
            'tags'         => 'nullable|array',
            'tags.*'       => 'integer|exists:news_tags,id',
            'author_id'    => 'nullable|integer|exists:users,id',
        ]);

        $data['cover_image'] = $this->resolveImage($request, $newsArticle->cover_image);

        if ($user->isAdmin() || $user->isOwner()) {
            $data['author_id'] = $data['author_id'] ?? $newsArticle->author_id;
        } else {
            $data['author_id'] = $newsArticle->author_id;
        }

        if ($data['status'] === 'published' && !$newsArticle->published_at && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $newsArticle->update($data);
        $newsArticle->tags()->sync($tags);

        return redirect()->route('admin.news.index')
            ->with('success', 'Article updated.');
    }

    public function destroy(NewsArticle $newsArticle): RedirectResponse
    {
        $this->authorizeArticle($newsArticle);

        $newsArticle->tags()->detach();
        $newsArticle->delete();

        return redirect()->route('admin.news.index')
            ->with('success', 'Article deleted.');
    }

    private function resolveImage(Request $request, ?string $existing = null): ?string
    {
        if ($request->hasFile('cover_image')) {
            $media = Media::createFromUpload($request->file('cover_image'), 'image', 'news');
            return $media->path;
        }

        if ($request->filled('cover_image_path')) {
            return $request->input('cover_image_path');
        }

        if ($request->input('cover_image_keep') === '0') {
            return null;
        }

        return $existing;
    }

    private function authorizeArticle(NewsArticle $article): void
    {
        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isOwner() && $article->author_id !== $user->id) {
            abort(403, 'You can only manage your own articles.');
        }
    }

    private function authorOptions()
    {
        $user = auth()->user();

        if (!$user->isAdmin() && !$user->isOwner()) {
            return collect();
        }

        return User::whereHas('roles', fn($q) => $q->whereIn('slug', ['owner', 'admin', 'broadcaster']))
            ->orderBy('name')
            ->get();
    }
}
