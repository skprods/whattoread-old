<?php

namespace App\Managers;

use App\Clients\RusTxtClient;
use App\Models\Book;
use App\Models\Word;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FrequenciesManager
{
    /** Симолы, которые необходимо заменить на пустую строку */
    private array $replacingSymbols;

    private int $totalWordsCount = 0;

    /** Символ неразрывного пробела, который часто встречается в тексте */
    private string $nbsp;

    private Book $book;

    /** Клиент для проверки морфологии */
    private RusTxtClient $client;

    private ThermFrequenciesManager $thermFrequencyManager;

    public function __construct()
    {
        $replacingSymbolsFile = file_get_contents(resource_path("dictionary/replacingSymbols.json"));
        $this->replacingSymbols = json_decode($replacingSymbolsFile, true);

        $this->nbsp = html_entity_decode("&nbsp;");

        $this->setClient();
        $this->thermFrequencyManager = app(ThermFrequenciesManager::class);
    }

    /** Формирование словника из файла */
    public function createFromFile(string $filePath, int $bookId)
    {
        $this->book = Book::findOrFail($bookId);

        $dictionary = $this->getDictionaryFromFile($filePath);
        $wordsCount = $this->totalWordsCount;

        $this->book = app(BookManager::class, ['book' => $this->book])->update(['words_count' => $wordsCount]);
        app(BookDictionaryManager::class)->createOrUpdate($dictionary, $this->book);

        $this->thermFrequencyManager->deleteForBook($this->book);
        $this->saveThermDictionary($dictionary);

        Storage::delete($filePath);
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

    /** Сохранение частотного словника */
    private function saveThermDictionary(Collection $dictionary)
    {
        $chunkedDictionary = $dictionary->chunk(1000);
        $chunkedDictionary->each(function (Collection $bookWordsFrequency) {
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
                $this->thermFrequencyManager->bulkCreate($thermDictionary, $this->book);
            }
        });
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
}
