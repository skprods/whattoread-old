<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookAssociation extends Model
{
    protected $fillable = [
        'association',
        'total',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public static function findAssociationForBook(string $association, int $bookId): ?BookAssociation
    {
        return self::query()->where('association', $association)->where('book_id', $bookId)->first();
    }
}
