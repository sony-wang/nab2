<?php

namespace Tests\Feature;

use App\Models\News;
use App\Services\ExternalNewsApiService;
use Filament\Notifications\Notification;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsSyncTest extends TestCase
{
    public function test_create_sends_the_legacy_payload_and_credentials(): void
    {
        config(['services.startup_api' => ['startup_news_url' => 'https://example.com/news', 'startup_key_id' => 'key', 'startup_secret' => 'secret']]);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/news/' => Http::response([], 201)]);
        $news = new News(['title' => '消息', 'type' => '公告', 'content' => 'https://example.com/article']);
        $news->id = 42;
        $news->updated_at = '2026-10-19 00:00:00';
        app(ExternalNewsApiService::class)->sync($news, true);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('X-API-KEY', 'key:secret')
            && $request['content_id'] === '0000000042'
            && $request['news_type'] === '公告'
            && $request['source'] === 'NTACADEMY'
            && $request['url'] === 'https://example.com/article');
        Notification::assertNotified('已同步到圓夢網');
    }

    public function test_update_failure_shows_a_warning(): void
    {
        config(['services.startup_api' => ['startup_news_url' => 'https://example.com/news', 'startup_key_id' => 'key', 'startup_secret' => 'secret']]);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/news/0000000042/' => Http::response([], 500)]);
        $news = new News(['title' => '消息', 'type' => '公告', 'content' => 'https://example.com/article']);
        $news->id = 42;
        $news->updated_at = '2026-10-19 00:00:00';
        app(ExternalNewsApiService::class)->sync($news, false);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT');
        Notification::assertNotified('本機已儲存，但圓夢網同步失敗');
    }

    public function test_missing_configuration_does_not_send_a_request(): void
    {
        config(['services.startup_api' => []]);
        Http::preventStrayRequests();
        app(ExternalNewsApiService::class)->sync(new News, true);
        Http::assertNothingSent();
        Notification::assertNotified('本機已儲存，但圓夢網同步失敗');
    }
}
