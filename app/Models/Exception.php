<?php

namespace App\Models;

use App\Traits\HasDatabaseCounter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $message
 * @property string $class
 * @property int $line
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Exception extends Model
{
    use HasFactory;
    use HasDatabaseCounter;

    protected $fillable = [
        'code',
        'message',
        'file',
        'line',
    ];
}
