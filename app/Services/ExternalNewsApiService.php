<?php

namespace App\Services;

use App\Models\News;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ExternalNewsApiService
{
    public function sync(News $news, bool $creating): void
    {
        try {
            $config = config('services.startup_api');
            if (empty($config['startup_news_url']) || empty($config['startup_key_id']) || empty($config['startup_secret'])) {
                throw new RuntimeException('Missing API configuration');
            }

            $id = str_pad((string) $news->id, 10, '0', STR_PAD_LEFT);
            $url = rtrim($config['startup_news_url'], '/');
            $payload = [
                'content_id' => $id,
                'news_type' => $news->type,
                'title' => $news->title,
                'content' => '<a href="'.e($news->content).'">'.e($news->content).'</a>('.$id.')',
                'publish_date' => $news->updated_at->timestamp,
                'source' => 'NTACADEMY',
                'url' => $news->content,
            ];
            $client = Http::acceptJson()->asJson()->withHeaders([
                'X-API-KEY' => $config['startup_key_id'].':'.$config['startup_secret'],
            ])->connectTimeout(3)->timeout(10);
            $response = $creating
                ? $client->post($url.'/', $payload)
                : $client->put($url.'/'.$id.'/', $payload);

            if (! $response->successful()) {
                throw new RuntimeException('API HTTP '.$response->status());
            }

            Notification::make()->title('已同步到圓夢網')->success()->send();
        } catch (Throwable $exception) {
            Log::warning('News sync failed', ['news_id' => $news->id, 'exception_type' => $exception::class]);
            Notification::make()->title('本機已儲存，但圓夢網同步失敗')
                ->body('請確認 API 設定或連線。若新增同步失敗，請先確認圓夢網是否已有此筆資料，避免重複送出。')
                ->warning()->persistent()->send();
        }
    }
}
