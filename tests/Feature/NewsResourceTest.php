<?php

namespace Tests\Feature;

use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\News\Pages\CreateNews;
use App\Filament\Resources\News\Pages\EditNews;
use App\Filament\Resources\News\Pages\ListNews;
use App\Models\News;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class NewsResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Schema::create('news', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('order_column')->default(1);
            $table->string('title');
            $table->string('type', 10)->nullable();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
        $this->get('/admin/news/create')->assertRedirect('/admin/login');
    }

    public function test_news_can_be_created_with_a_normalized_url(): void
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
        Livewire::test(CreateNews::class)
            ->fillForm(['title' => '測試消息', 'type' => '公告', 'content' => 'example.com/news'])
            ->call('create')->assertHasNoFormErrors()->assertRedirect(NewsResource::getUrl('index'));
        $this->assertDatabaseHas('news', ['title' => '測試消息', 'content' => 'https://example.com/news', 'order_column' => 1]);
        Http::assertNothingSent();
    }

    public function test_enabling_sync_sends_news_after_saving(): void
    {
        config(['services.startup_api' => ['startup_news_url' => 'https://example.com/news', 'startup_key_id' => 'key', 'startup_secret' => 'secret']]);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/news/' => Http::response([], 201)]);
        $this->actingAs(User::factory()->create());
        Livewire::test(CreateNews::class)
            ->fillForm(['title' => '同步消息', 'type' => '公告', 'content' => 'example.com/news', 'sync_now' => true])
            ->call('create')->assertHasNoFormErrors()->assertRedirect(NewsResource::getUrl('index'));
        $this->assertDatabaseHas('news', ['title' => '同步消息']);
        Http::assertSentCount(1);
    }

    public function test_update_sync_failure_preserves_local_changes(): void
    {
        config(['services.startup_api' => ['startup_news_url' => 'https://example.com/news', 'startup_key_id' => 'key', 'startup_secret' => 'secret']]);
        Http::preventStrayRequests();
        Http::fake(['https://example.com/news/0000000001/' => Http::failedConnection()]);
        $this->actingAs(User::factory()->create());
        $news = News::create(['title' => '原標題', 'type' => '公告', 'content' => 'https://example.com']);
        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])
            ->fillForm(['title' => '新標題', 'sync_now' => true])
            ->call('save')->assertHasNoFormErrors()->assertRedirect(NewsResource::getUrl('index'))
            ->assertNotified('本機已儲存，但圓夢網同步失敗');
        $this->assertDatabaseHas('news', ['id' => $news->id, 'title' => '新標題']);
    }

    public function test_invalid_news_is_not_saved(): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(CreateNews::class)
            ->fillForm(['title' => '', 'type' => null, 'content' => 'javascript:alert(1)'])
            ->call('create')->assertHasFormErrors(['title' => 'required', 'type' => 'required', 'content' => 'url']);
        $this->assertDatabaseCount('news', 0);
    }

    public function test_imported_news_can_be_listed_searched_and_edited(): void
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
        $news = News::create(['title' => '舊資料', 'type' => null, 'content' => 'https://example.com/old', 'order_column' => 8]);
        Livewire::test(ListNews::class)->assertCanSeeTableRecords([$news])->searchTable('不存在')->assertCanNotSeeTableRecords([$news]);
        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])
            ->fillForm(['title' => '更新消息', 'type' => '創業專欄', 'content' => 'https://example.com/new'])
            ->call('save')->assertHasNoFormErrors()->assertRedirect(NewsResource::getUrl('index'));
        $this->assertDatabaseHas('news', ['id' => $news->id, 'title' => '更新消息', 'order_column' => 8]);
        Http::assertNothingSent();
    }

    public function test_news_can_be_reordered_and_new_records_append(): void
    {
        $this->actingAs(User::factory()->create());
        $first = News::create(['title' => '第一則', 'content' => 'https://example.com/1', 'order_column' => 8]);
        $second = News::create(['title' => '第二則', 'content' => 'https://example.com/2']);
        $this->assertSame(9, $second->order_column);
        Livewire::test(ListNews::class)->call('reorderTable', [$second->id, $first->id]);
        $this->assertDatabaseHas('news', ['id' => $second->id, 'order_column' => 1]);
        $this->assertDatabaseHas('news', ['id' => $first->id, 'order_column' => 2]);
    }

    public function test_news_can_be_deleted_without_external_requests(): void
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
        $news = News::create(['title' => '待刪除', 'content' => 'https://example.com']);
        Livewire::test(EditNews::class, ['record' => $news->getRouteKey()])->callAction(DeleteAction::class);
        $this->assertDatabaseMissing('news', ['id' => $news->id]);
        Http::assertNothingSent();
    }
}
