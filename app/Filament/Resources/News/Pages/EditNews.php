<?php

namespace App\Filament\Resources\News\Pages;

use App\Filament\Resources\News\NewsResource;
use App\Services\ExternalNewsApiService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNews extends EditRecord
{
    protected static string $resource = NewsResource::class;

    protected function afterSave(): void
    {
        if ($this->data['sync_now'] ?? false) {
            app(ExternalNewsApiService::class)->sync($this->record, false);
        }
    }

    protected function getRedirectUrl(): string
    {
        return NewsResource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
