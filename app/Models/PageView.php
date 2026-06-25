<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $fillable = [
        'url_path',
        'views_count',
    ];

    /**
     * Increment the page views count atomically.
     */
    public static function incrementView(string $path): int
    {
        $cleanPath = $path === '/' ? '/' : rtrim($path, '/');

        // Buscar o crear el registro
        $pageView = self::firstOrCreate(
            ['url_path' => $cleanPath],
            ['views_count' => 0]
        );

        // Incrementar de forma atómica en la base de datos
        $pageView->increment('views_count');

        return $pageView->views_count;
    }
}
