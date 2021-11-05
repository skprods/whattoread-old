<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public const SHOP_BUKVOED = 'Буквоед';

    protected $fillable = [
        'title',
        'description',
        'series',
        'publisher_name',
        'publisher_year',
        'author',
        'category',
        'shop_url',
        'shop_name',
        'shop_book_id',
    ];

    public static function findByShopBookIdAndShopName(int $shopBookId, string $shopName): Book|null
    {
        return self::query()
            ->where('shop_name', '=', $shopName)
            ->where('shop_book_id', '=', $shopBookId)
            ->first();
    }
}
