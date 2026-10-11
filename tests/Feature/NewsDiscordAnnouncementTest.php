<?php

namespace Tests\Feature;

use App\Jobs\AnnounceNewsArticleToDiscord;
use App\Models\NewsArticle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// User-directed 2026-10: a newly published news article is posted to the Discord news
// channel (DISCORD_NEWS_WEBHOOK) — once, and not before the article is public.
class NewsDiscordAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK = 'https://discord.com/api/webhooks/1/test';

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        return $admin;
    }

    private function article(array $attributes = []): NewsArticle
    {
        return NewsArticle::create($attributes + [
            'title' => 'Season finale', 'excerpt' => 'It all comes down to Spa.', 'body' => '<p>Body</p>',
            'author_id' => User::factory()->create()->id, 'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
    }

    public function test_publishing_an_article_queues_the_discord_post_once(): void
    {
        Bus::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.news.store'), ['title' => 'Draft', 'status' => 'draft'])
            ->assertSessionHasNoErrors();
        Bus::assertNotDispatched(AnnounceNewsArticleToDiscord::class);

        // Draft → published: announced.
        $draft = NewsArticle::where('title', 'Draft')->first();
        $this->actingAs($admin)->put(route('admin.news.update', $draft), ['title' => 'Draft', 'status' => 'published'])
            ->assertSessionHasNoErrors();
        Bus::assertDispatched(AnnounceNewsArticleToDiscord::class, fn ($job) => $job->articleId === $draft->id);

        // Editing it afterwards doesn't announce it again.
        $this->actingAs($admin)->put(route('admin.news.update', $draft), ['title' => 'Draft, edited', 'status' => 'published']);
        Bus::assertDispatchedTimes(AnnounceNewsArticleToDiscord::class, 1);

        // Published straight away on creation.
        $this->actingAs($admin)->post(route('admin.news.store'), ['title' => 'Breaking', 'status' => 'published']);
        Bus::assertDispatchedTimes(AnnounceNewsArticleToDiscord::class, 2);
    }

    public function test_an_article_scheduled_ahead_is_announced_at_its_publish_time(): void
    {
        Bus::fake();
        $publishAt = now()->addDay()->startOfMinute();

        $this->actingAs($this->admin())->post(route('admin.news.store'), [
            'title' => 'Tomorrow', 'status' => 'published', 'published_at' => $publishAt->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        Bus::assertDispatched(AnnounceNewsArticleToDiscord::class, fn ($job) => $publishAt->equalTo($job->delay));
    }

    public function test_the_post_links_to_the_article(): void
    {
        config(['services.discord.news_webhook' => self::WEBHOOK]);
        Http::fake([self::WEBHOOK => Http::response(null, 204)]);
        $article = $this->article();

        (new AnnounceNewsArticleToDiscord($article->id))->handle();

        Http::assertSent(fn (Request $r) => $r->url() === self::WEBHOOK
            && $r['embeds'][0]['title'] === 'Season finale'
            && $r['embeds'][0]['url'] === route('news.show', $article->slug)
            && $r['embeds'][0]['description'] === 'It all comes down to Spa.');
    }

    public function test_nothing_is_posted_for_a_draft_or_without_a_webhook(): void
    {
        Http::fake();

        config(['services.discord.news_webhook' => self::WEBHOOK]);
        (new AnnounceNewsArticleToDiscord($this->article(['status' => 'draft'])->id))->handle();

        config(['services.discord.news_webhook' => null]);
        (new AnnounceNewsArticleToDiscord($this->article(['title' => 'Other'])->id))->handle();

        Http::assertNothingSent();
    }
}
