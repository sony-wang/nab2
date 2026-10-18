<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = ['title', 'type', 'content', 'order_column'];

    protected static function booted(): void
    {
        static::creating(function (News $news): void {
            $news->order_column ??= ((int) static::max('order_column')) + 1;
        });
    }
}
