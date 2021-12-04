<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $model
 * @property int $model_id
 * @property string $action
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Stat extends Model
{
    use HasFactory;

    public const BOOK_MODEL = 'book';
    public const GENRE_MODEL = 'genre';
    public const TELEGRAM_CHAT_MODEL = 'telegramChat';
    public const TELEGRAM_USER_MODEL = 'telegramUser';
    public const USER_MODEL = 'user';
    public const USER_BOOK_ASSOCIATION_MODEL = 'userBookAssociation';

    public const CREATED_ACTION = 'created';
    public const DELETED_ACTION = 'deleted';

    protected $fillable = [
        'model',
        'model_id',
        'action',
    ];
}
