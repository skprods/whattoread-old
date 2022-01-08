<?php

namespace App\Managers\Dictionaries;

use App\Clients\RusTxtClient;
use App\Facades\Dictionary;
use App\Managers\BookDictionaryManager;
use App\Managers\BookFrequenciesManager;
use App\Managers\BookManager;
use App\Models\Book;
use App\Models\Word;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class FrequencyManager
{
    private bool $debug;
    private Book $book;

    /** Клиент для проверки морфологии */
    private RusTxtClient $client;

    private BookFrequenciesManager $bookFrequenciesManager;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
        $this->setClient();
        $this->bookFrequenciesManager = app(BookFrequenciesManager::class);
    }

    /** Формирование словника из файла */
    public function createContentFrequencyFromFile(string $filePath, int $bookId)
    {
        $this->book = Book::findOrFail($bookId);
        $bookName = "{$this->book->author} - {$this->book->title}";
        $this->log("Начинается составление частотного словника по содержанию книги #{$this->book->id}: $bookName");

        $dictionary = Dictionary::createFromFile($filePath, DictionaryManager::FB2_EXTENSION);
        $this->log("Словарь подготовлен");

        $wordsCount = $dictionary->count();
        $this->log("Слов в книге: $wordsCount");

        $dictionary = $dictionary->all();

        $this->book = app(BookManager::class, ['book' => $this->book])->update(['words_count' => $wordsCount]);
        app(BookDictionaryManager::class)->createOrUpdate($dictionary, $this->book);
        $this->log("Словарь сохранён в таблицу book_dictionary");

        $this->bookFrequenciesManager->deleteContentFrequency($this->book);
        $this->log("Словарь терминов по содержимому для книги очищен");

        $thermsCount = $this->saveDictionary($dictionary, $this->book->words_count, 'content');
        $this->log("Словарь терминов по содержимому успешно наполнен");

        app(BookManager::class, ['book' => $this->book])->update(['therms_count' => $thermsCount]);
        $this->log("Количество терминов обновлено");

        unlink($filePath);
        $this->log("Файл успешно удалён");
    }

    public function createDescriptionFrequency(Book $book)
    {
        $this->book = $book;
        $bookName = "{$this->book->author} - {$this->book->title}";
        $this->log("Начинается составление частотного словника по описанию книги #{$this->book->id}: $bookName");

        if ($this->book->description === null || $this->book->description === '') {
            $this->log("У книги #{$this->book->id}: $bookName нет описания. Импорт отменён");
            return;
        }

        $dictionary = Dictionary::createFromString($this->book->description);
        $this->log("Словарь подготовлен");

        $wordsCount = $dictionary->count();
        $this->log("Слов в описании: $wordsCount");

        if ($wordsCount < 10) {
            $this->log("Слов в описании слишком мало, словник составлен не будет");
            return;
        }

        $this->bookFrequenciesManager->deleteDescriptionFrequency($this->book);
        $this->log("Словарь терминов по описанию для книги очищен");

        $dictionary = $dictionary->all();

        $this->saveDictionary($dictionary, $wordsCount, 'description');
        $this->log("Словарь терминов по описанию успешно наполнен");
    }

    /** Сохранение частотного словника  */
    private function saveDictionary(Collection $dictionary, int $wordsCount, string $type): int
    {
        /** Количество отобранных терминов */
        $thermsCount = 0;
        /** Чтобы не грузить базу огромным запросом, разбиваем на коллекции по 1000 слов */
        $chunkedDictionary = $dictionary->chunk(1000);

        /**
         * Начало обработки, проходим по каждому элементу (т.е. по коллекции), определяем нужные
         * термины и вставляем их в БД
         */
        $chunkedDictionary->each(function (Collection $bookWordsFrequency) use (&$thermsCount, $wordsCount, $type) {
            /** Коллекция отобранных терминов, наполняется по принципу word => frequency */
            $thermDictionary = collect();
            /** Вспомогательный массив для фиксации, какие слова нужно добавить в БД (если таких ещё нет) */
            $wordKeys = [];

            /**
             * Формируем запрос на получение тех слов из словаря, которые уже
             * есть в базе данных. Получаем их в коллекцию $words
             */
            $builder = Word::query();
            $bookWordsFrequency->keys()->each(function ($word) use ($builder, &$wordKeys) {
                $builder->orWhere('word', $word);
                /** Для дальнейшей проверки также заполняем массив вида слово => слово */
                $wordKeys[$word] = $word;
            });
            $words = $builder->get();

            /**
             * Проходим по каждому полученному из базы слову и добавляем его в
             * $thermDictionary, если выполняются все условия
             */
            $words->each(function (Word $word) use (&$thermDictionary, $bookWordsFrequency, &$wordKeys, $wordsCount) {
                /** Если нет типа (сущ, прл, гл и тд), получаем тип и сохраняем */
                if (!$word->type) {
                    $word->type = $this->getType($word->word);
                    $word->save();
                }

                /** Для словаря терминов используем только существительные и прилагательные */
                if ($word->type === 'сущ' || $word->type === 'прл') {
                    $thermDictionary->put($word->id, $bookWordsFrequency->get($word->word) / $wordsCount);
                }

                /** Удаляем слово из массива - оно получено из базы */
                unset($wordKeys[$word->word]);
            });

            /**
             * Все остальные слова, которых не нашлось в базе, создаём и добавляем в базу.
             * Вместе с этим проверяем выполнение условий и добавляем их в $thermDictionary
             */
            foreach ($wordKeys as $wordKey) {
                $word = $this->createWord($wordKey);

                if ($word && ($word->type === 'сущ' || $word->type === 'прл')) {
                    $thermDictionary->put($word->id, $bookWordsFrequency->get($word->word) / $wordsCount);
                }
            }

            /**
             * Если есть отобранные слова, добавляем их в БД
             */
            if ($thermDictionary->count()) {
                switch ($type) {
                    case "content":
                        $insertedCount = $this->bookFrequenciesManager
                            ->addContentFrequencies($thermDictionary, $this->book);
                        break;
                    case "description":
                        $insertedCount = $this->bookFrequenciesManager
                            ->addDescriptionFrequencies($thermDictionary, $this->book);
                        break;
                    default:
                        $insertedCount = 0;
                }

                $this->log("Вставлено $insertedCount терминов");
                $thermsCount += $insertedCount;
            }
        });

        return $thermsCount;
    }

    private function createWord(string $wordKey): ?Word
    {
        try {
            $type = $this->getType($wordKey);
        } catch (RequestException $exception) {
            Log::error($exception->getMessage());
            $this->setClient();
            $type = $this->getType($wordKey);
        }

        if ($type) {
            /** @var Word $word */
            $word = app(Word::class);
            $word->word = $wordKey;
            $word->type = $type;
            $word->save();

            return $word;
        }

        return null;
    }

    private function getType(string $word): ?string
    {
        $content = $this->client->getMorphologyForWord($word);

        preg_match('<meta name="description" content="(.*?)часть речи:(.*?),(.*?)">', $content, $matches);
        if (isset($matches[2])) {
            $partOfSpeech = trim($matches[2]);

            $types = [
                'местоимение-существительное' => 'мест',
                'местоименное прилагательное' => 'прл',
                'частица' => 'част',
                'междометие' => 'межд',
                'прилагательное' => 'прл',
                'причастие' => 'прч',
                'существительное' => 'сущ',
                'наречие' => 'нар',
                'глагол в личной форме' => 'гл',
                'инфинитив' => 'гл',
                'деепричастие' => 'дееп',
                'союз' => 'союз',
                'предлог' => 'предл',
                'фразеологизм' => 'фраз',
                'предикатив' => 'предик',
                'местоимение-предикатив' => 'предик',
                'вводное слово' => 'ввод',
            ];

            if (str_contains($partOfSpeech, 'прилагательное')) {
                return 'прл';
            }

            if (str_contains($partOfSpeech, 'числительное')) {
                return 'числ';
            }

            if (str_contains($partOfSpeech, 'причастие')) {
                return 'прч';
            }

            return $types[$partOfSpeech] ?? null;
        }

        return null;
    }

    private function setClient()
    {
        $this->client = new RusTxtClient();
    }

    public function log(string $message)
    {
        if ($this->debug) {
            Console::info("[frequencyBook #{$this->book->id}] $message");
        }

        Log::info("[frequencyBook #{$this->book->id}] $message");
    }
}