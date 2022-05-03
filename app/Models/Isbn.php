<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $book_id
 * @property Book $book
 * @property string $code
 * @property Carbon $created_at
 *  @property Carbon $updated_at
 */
class Isbn extends Model
{
    protected $fillable = [
        'book_id',
        'code',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
