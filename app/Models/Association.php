<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property-read Word $word
 * @property-read Word $association
 * @property int $total
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'total',
    ];

    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Word::class, 'association_word_id');
    }
}
