<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcasterRoleTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwner(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'owner')->first());

        return $user;
    }

    public function test_broadcaster_can_view_create_and_store_news_articles(): void
    {
        $broadcaster = User::factory()->broadcaster()->create();

        $this->actingAs($broadcaster)->get(route('admin.news.index'))->assertOk();
        $this->actingAs($broadcaster)->get(route('admin.news.create'))->assertOk();

        $response = $this->actingAs($broadcaster)->post(route('admin.news.store'), [
            'title'  => 'A Broadcaster Article',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.news.index'));
        $this->assertDatabaseHas('news_articles', [
            'title'     => 'A Broadcaster Article',
            'author_id' => $broadcaster->id,
        ]);
    }

    public function test_broadcaster_cannot_manage_another_authors_article(): void
    {
        $broadcaster     = User::factory()->broadcaster()->create();
        $otherBroadcaster = User::factory()->broadcaster()->create();

        $article = NewsArticle::create([
            'title'     => 'Someone Elses Article',
            'status'    => 'draft',
            'author_id' => $otherBroadcaster->id,
        ]);

        $this->actingAs($broadcaster)->get(route('admin.news.edit', $article))->assertForbidden();
        $this->actingAs($broadcaster)->put(route('admin.news.update', $article), [
            'title'  => 'Hijacked',
            'status' => 'draft',
        ])->assertForbidden();
        $this->actingAs($broadcaster)->delete(route('admin.news.destroy', $article))->assertForbidden();
    }

    public function test_broadcaster_cannot_reassign_article_author_on_store(): void
    {
        $broadcaster = User::factory()->broadcaster()->create();
        $otherUser   = User::factory()->broadcaster()->create();

        $this->actingAs($broadcaster)->post(route('admin.news.store'), [
            'title'     => 'Author Spoof Attempt',
            'status'    => 'draft',
            'author_id' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('news_articles', [
            'title'     => 'Author Spoof Attempt',
            'author_id' => $broadcaster->id,
        ]);
    }

    public function test_broadcaster_cannot_access_unrelated_admin_areas(): void
    {
        $broadcaster = User::factory()->broadcaster()->create();

        $this->actingAs($broadcaster)->get(route('admin.races.index'))->assertForbidden();
        $this->actingAs($broadcaster)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_owner_can_manage_any_broadcasters_article(): void
    {
        $owner       = $this->makeOwner();
        $broadcaster = User::factory()->broadcaster()->create();

        $article = NewsArticle::create([
            'title'     => 'Broadcaster Written, Owner Edited',
            'status'    => 'draft',
            'author_id' => $broadcaster->id,
        ]);

        $this->actingAs($owner)->get(route('admin.news.edit', $article))->assertOk();
        $this->actingAs($owner)->put(route('admin.news.update', $article), [
            'title'  => 'Edited By Owner',
            'status' => 'draft',
        ])->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseHas('news_articles', ['id' => $article->id, 'title' => 'Edited By Owner']);
    }
}