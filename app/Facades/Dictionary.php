<?php

namespace App\Facades;

use App\Services\DictionaryService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Entities\Dictionary createFromFile(string $filePath, string $type = null)
 * @method static \App\Entities\Dictionary createFromString(string $string)
 *
 * @see DictionaryService
 */
class Dictionary extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dictionary';
    }
}