<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $book_id
 * @property Book $book
 * @property array|null $description - вектор по описанию книги
 * @property array|null $content     - вектор по содержанию книги
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookVector extends Model
{
    public $incrementing = false;
    protected $primaryKey = 'book_id';

    protected $fillable = [
        'book_id',
        'description',
        'content',
    ];

    protected $casts = [
        'description' => 'array',
        'content' => 'array',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
