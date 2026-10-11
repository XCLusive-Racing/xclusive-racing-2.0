<?php

namespace App\Jobs;

use App\Models\NewsArticle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Posts a newly published news article to the Discord news channel
// (DISCORD_NEWS_WEBHOOK). Dispatched once, when the article is first published
// (Admin\NewsArticleController) — delayed until published_at for one scheduled ahead,
// so the link in the post never leads to an article that isn't public yet.
class AnnounceNewsArticleToDiscord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $articleId) {}

    public static function dispatchFor(NewsArticle $article): void
    {
        $job = self::dispatch($article->id);

        if ($article->published_at?->isFuture()) {
            $job->delay($article->published_at);
        }
    }

    public function handle(): void
    {
        $webhook = config('services.discord.news_webhook');
        $article = NewsArticle::with('author')->find($this->articleId);

        // Deleted or back to draft since it was queued: nothing to announce.
        if (! $webhook || ! $article || $article->status !== 'published' || ! $article->published_at) {
            return;
        }

        // Its publish date was moved further ahead: wait for the new one.
        if ($article->published_at->isFuture()) {
            self::dispatchFor($article);

            return;
        }

        $embed = array_filter([
            'title' => Str::limit($article->title, 250),
            'url' => route('news.show', $article->slug),
            'description' => $article->excerpt ?: Str::limit(trim(strip_tags((string) $article->body)), 200),
            'color' => 0x7C3AED,
            'image' => $article->cover_image_url ? ['url' => $article->cover_image_url] : null,
            'footer' => $article->author ? ['text' => 'By '.$article->author->displayName()] : null,
            'timestamp' => $article->published_at->toIso8601String(),
        ]);

        Http::timeout(10)->post($webhook, [
            'content' => '📰 **New on XCLusive Racing**',
            'embeds' => [$embed],
            'allowed_mentions' => ['parse' => []],
        ])->throw();
    }
}
