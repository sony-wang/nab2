<?php

namespace App\Filament\Resources\News\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->columns([
                TextColumn::make('order_column')->label('排序')->sortable(),
                TextColumn::make('title')->label('標題')->searchable()->wrap(),
                TextColumn::make('type')->label('分類')->placeholder('未分類')->badge(),
                TextColumn::make('created_at')->label('建立時間')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('updated_at')->label('更新時間')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('分類')->options([
                    '新聞快訊' => '新聞快訊',
                    '創業專欄' => '創業專欄',
                    '公告' => '公告',
                ]),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
