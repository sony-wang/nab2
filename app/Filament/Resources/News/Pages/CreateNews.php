<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Services\ExternalNewsApiService;
use Filament\Resources\Pages\CreateRecord;

class CreateNews extends CreateRecord
{
    protected static string $resource = NewsResource::class;

    protected function afterCreate(): void
    {
        if ($this->data['sync_now'] ?? false) {
            app(ExternalNewsApiService::class)->sync($this->record, true);
        }
    }

    protected function getRedirectUrl(): string
    {
        return NewsResource::getUrl('index');
    }
}
