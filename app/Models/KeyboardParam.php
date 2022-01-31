<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $update_id
 * @property string $param
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class KeyboardParam extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_id',
        'param',
    ];

    public static function findByUpdateId(int $updateId): ?self
    {
        return self::query()->where('update_id', $updateId)->first();
    }
}
