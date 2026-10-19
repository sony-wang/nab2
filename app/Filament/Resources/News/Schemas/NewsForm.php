<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class NewsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('sync_now')->label('同步到圓夢網')->default(false)->dehydrated(false)
                ->helperText('開啟後，儲存時會將這則消息同步到圓夢網。')->columnSpanFull(),
            TextInput::make('title')->label('標題')->required()->maxLength(255)->columnSpanFull(),
            Select::make('type')->label('分類')->options([
                '新聞快訊' => '新聞快訊',
                '創業專欄' => '創業專欄',
                '公告' => '公告',
            ])->default('新聞快訊')->required(),
            TextInput::make('content')->label('連結網址')
                ->required()->maxLength(2048)
                ->mutateStateForValidationUsing(fn (?string $state): string => self::normalizeUrl($state))
                ->rules(['url:http,https'])
                ->dehydrateStateUsing(fn (?string $state): string => self::normalizeUrl($state))
                ->helperText('可省略 https://，儲存時會自動補上。')->columnSpanFull(),
        ]);
    }

    private static function normalizeUrl(?string $url): string
    {
        $url = trim($url ?? '');

        if ($url === '' || preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return $url;
        }

        return 'https://'.$url;
    }
}
