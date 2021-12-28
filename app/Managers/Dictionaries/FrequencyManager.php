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
    /** Симолы, которые необходимо заменить на пустую строку */
    private array $replacingSymbols;

    private int $totalWordsCount = 0;

    /** Символ неразрывного пробела, который часто встречается в тексте */
    private string $nbsp;

    private Book $book;

    /** Клиент для проверки морфологии */
    private RusTxtClient $client;

    private BookFrequenciesManager $bookFrequenciesManager;

    public function __construct()
    {
        $replacingSymbolsFile = file_get_contents(resource_path("dictionary/replacingSymbols.json"));
        $this->replacingSymbols = json_decode($replacingSymbolsFile, true);

        $this->nbsp = html_entity_decode("&nbsp;");

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

        $thermsCount = $this->saveContentDictionary($dictionary);
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

        $this->saveDescriptionDictionary($dictionary, $wordsCount);
        $this->log("Словарь терминов по описанию успешно наполнен");
    }

    /**
     * Состаление словаря из файла
     * Структура файла - fb2, т.е. обычный xml.
     *
     * Нужно пройти по каждой строке внутри тега <body> в файле и распарсить
     * слова по пробелам. Из них составляется коллекция, которая сортируется
     * по убыванию частотности (сначала самые частые).
     */
    private function getDictionaryFromFile(string $filePath): Collection
    {
        $file = fopen(storage_path('app/') . $filePath, 'r');

        /** Флаг, указывающий, что текущая строка находится внутри <body> */
        $isBody = false;
        $dictionary = [];

        while ($row = fgets($file)) {
            /** Не во всех файлах идёт чистый <body>, иногда с доп параметрами, поэтому без > */
            if (str_contains($row, '<body')) {
                $isBody = true;
            }

            /** После </body> иногда бывает картинка, её парсить не нужно */
            if (str_contains($row, '</body>')) {
                $isBody = false;
            }

            if ($isBody) {
                $this->setWordsFromRow($row, $dictionary);
            }
        }

        return collect($dictionary)->sortDesc();
    }

    /** Извлечение слов из строки + обновление общего числа слов */
    private function setWordsFromRow(string $row, array &$dictionary)
    {
        /** Удаление ненужных символов и тегов */
        $row = $this->prepareRow($row);

        $words = preg_split('/ +/', $row);

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $this->totalWordsCount++;

            if (isset($dictionary[$word])) {
                $dictionary[$word] += 1;
            } else {
                $dictionary[$word] = 1;
            }
        }
    }

    /** Подготовка строки к разбивке на частотный словник */
    private function prepareRow(string $row): string
    {
        /** убираем теги */
        $row = strip_tags($row);

        /** удаляем запрещённые символы */
        $row = $this->deleteForbiddenSymbols($row);

        return mb_strtolower(trim($row));
    }

    private function deleteForbiddenSymbols(string $row): string
    {
        $row = str_replace($this->nbsp, '', $row);

        foreach ($this->replacingSymbols as $symbol) {
            $row = str_replace($symbol, '', $row);
        }

        return $row;
    }

    /** Сохранение частотного словника по содержанию */
    private function saveContentDictionary(Collection $dictionary): int
    {
        $thermsCount = 0;
        $chunkedDictionary = $dictionary->chunk(1000);
        $chunkedDictionary->each(function (Collection $bookWordsFrequency) use (&$thermsCount) {
            $thermDictionary = collect();
            $wordKeys = [];

            /** Формируем запрос на получение всех слов из базы данных */
            $builder = Word::query();
            $bookWordsFrequency->keys()->each(function ($word) use ($builder, &$wordKeys) {
                $builder->orWhere('word', $word);
                /** Для дальнейшей проверки также заполняем массив вида слово => слово */
                $wordKeys[$word] = $word;
            });
            $words = $builder->get();

            /** Проходим по каждому полученному из базы слову и добавляем его, если выполняются все условия */
            $words->each(function (Word $word) use (&$thermDictionary, $bookWordsFrequency, &$wordKeys) {
                /** Если нет типа (сущ, прл, гл и тд), получаем тип и сохраняем */
                if (!$word->type) {
                    $word->type = $this->getType($word->word);
                    $word->save();
                }

                /** Для словаря терминов используем только существительные и прилагательные */
                if ($word->type === 'сущ' || $word->type === 'прл') {
                    $thermDictionary->put($word->id, $bookWordsFrequency->get($word->word) / $this->book->words_count);
                }

                /** Удаляем слово из массива - оно получено из базы */
                unset($wordKeys[$word->word]);
            });

            /** Все остальные слова, которых не нашлось в базе, создаём и добавляем в базу */
            foreach ($wordKeys as $wordKey) {
                $word = $this->createWord($wordKey);

                if ($word && ($word->type === 'сущ' || $word->type === 'прл')) {
                    $thermDictionary->put($word->id, $bookWordsFrequency->get($word->word) / $this->book->words_count);
                }
            }

            if ($thermDictionary->count()) {
                $insertedCount = $this->bookFrequenciesManager->addContentFrequencies($thermDictionary, $this->book);
                $this->log("Вставлено $insertedCount терминов");
                $thermsCount += $insertedCount;
            }
        });

        return $thermsCount;
    }

    /** Сохранение частотного словника по описанию */
    private function saveDescriptionDictionary(Collection $dictionary, int $total): int
    {
        $thermsCount = 0;
        $thermDictionary = collect();
        $wordKeys = [];

        /** Формируем запрос на получение всех слов из базы данных */
        $builder = Word::query();
        $dictionary->keys()->each(function ($word) use ($builder, &$wordKeys) {
            $builder->orWhere('word', $word);
            /** Для дальнейшей проверки также заполняем массив вида слово => слово */
            $wordKeys[$word] = $word;
        });
        $words = $builder->get();

        /** Проходим по каждому полученному из базы слову и добавляем его, если выполняются все условия */
        $words->each(function (Word $word) use (&$thermDictionary, $dictionary, &$wordKeys, $total) {
            /** Если нет типа (сущ, прл, гл и тд), получаем тип и сохраняем */
            if (!$word->type) {
                $word->type = $this->getType($word->word);
                $word->save();
            }

            $thermDictionary->put($word->id, $dictionary->get($word->word) / $total);

            /** Удаляем слово из массива - оно получено из базы */
            unset($wordKeys[$word->word]);
        });

        /** Все остальные слова, которых не нашлось в базе, создаём и добавляем в базу */
        foreach ($wordKeys as $wordKey) {
            $word = $this->createWord($wordKey);

            if ($word) {
                $thermDictionary->put($word->id, $dictionary->get($word->word) / $total);
            }
        }

        if ($thermDictionary->count()) {
            $insertedCount = $this->bookFrequenciesManager->addDescriptionFrequencies($thermDictionary, $this->book);
            $this->log("Вставлено $insertedCount терминов");
        }

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
        if (config('app.debug')) {
            Console::info("[frequencyBook #{$this->book->id}] $message");
        }

        Log::info("[frequencyBook #{$this->book->id}] $message");
    }
}
