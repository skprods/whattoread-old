<?php

namespace App\Services;

use App\Drivers\DictionaryDriver;
use App\Drivers\WordExtractionManager;
use App\Entities\Dictionary;
use App\Exceptions\FileTypeNotAllowedException;

class DictionaryService
{
//    private const TXT_EXTENSION = 'txt';
    public const FB2_EXTENSION = 'fb2';

    public const ALLOWED_EXTENSIONS = [
//        self::TXT_EXTENSION => self::TXT_EXTENSION,
        self::FB2_EXTENSION => self::FB2_EXTENSION,
    ];

    /**
     * Create new Dictionary instance from file
     *
     * This function will create collection of words with their
     * features and will return new Dictionary instance with
     * this collection.
     *
     * Allowed file extensions you can see in ALLOWED_EXTENSIONS
     * constant. Filename may not have an extension, but in this
     * case the $type parameter must be passed. Otherwise you will
     * get @throws FileTypeNotAllowedException
     *
     * @param string $filePath      - path to file on the server
     * @param string|null $type     - file type. allowed: txt, fb2
     *
     * @return Dictionary           - new Dictionary instance
     */
    public static function createFromFile(string $filePath, string $type = null): Dictionary
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (!array_key_exists($fileExtension, self::ALLOWED_EXTENSIONS) && !$type) {
            throw new FileTypeNotAllowedException();
        }

        /** @var WordExtractionManager $manager */
        $manager = app(WordExtractionManager::class, ['pathOrString' => $filePath]);

        $extension = $type ?? $fileExtension;

        /** @var DictionaryDriver $driver */
        switch ($extension) {
            case self::FB2_EXTENSION:
                $driver = $manager->driver('fb2');
                break;
        }

        $dictionary = $driver->getDictionary();

        return new Dictionary($dictionary);
    }

    public static function createFromString(string $string): Dictionary
    {
        /** @var WordExtractionManager $manager */
        $manager = app(WordExtractionManager::class, ['pathOrString' => $string]);

        /** @var DictionaryDriver $driver */
        $driver = $manager->driver('text');
        $dictionary = $driver->getDictionary();

        return new Dictionary($dictionary);
    }
}