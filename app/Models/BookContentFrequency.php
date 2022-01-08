<?php

namespace App\Models;

use App\Traits\HasDatabaseBooksCounter;
use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Book $book
 * @property-read Word $word
 * @property double $frequency
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class BookContentFrequency extends Model
{
    use HasDatabaseCounter;
    use HasDatabaseBooksCounter;

    protected $fillable = [
        'frequency',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }
}
