<?php

namespace App\Console\Commands;

use App\Enums\WordTypeFromPos;
use App\Models\Word;
use cijic\phpMorphy\Morphy;
use Illuminate\Console\Command;

class LoadWordsDictionary extends Command
{
    protected $signature = 'words:loadDictionary';

    protected $description = 'Добавление слов из словаря с векторами';

    private Morphy $morphy;

    public function handle()
    {
        $this->morphy = new Morphy();
        $file = fopen(storage_path('app/model.txt'), "r");

        $words = [];
        $rows = 1;

        while ($row = fgets($file)) {
            $this->info("Текущая строка: #$rows");
            $rows++;

            $items = explode(' ', $row);
            $items[0] = preg_replace("/\d/", '', $items[0]);
            $items[0] = str_replace('-', '', $items[0]);

            if (str_contains($row, "xxx")) {
                continue;
            }

            $wordData = explode("_", array_shift($items));
            if (count($wordData) !== 2) {
                continue;
            }

            $word = preg_replace("/::(.*)/", '', $wordData[0]);
            $type = $this->getType($word, $wordData[1]);

            if ($type === null) {
                continue;
            }

            $words[] = [
                'word' => $word,
                'type' => $type,
                'vector' => json_encode($items),
            ];

            if (count($words) === 1000) {
                $this->saveWords($words);
                unset($words);
                $words = [];
            }
        }

        $this->saveWords($words);

        fclose($file);
        $this->info("Наполнение словаря завершено.");
    }

    private function saveWords(array $words)
    {
        $this->info('Вставка данных в БД...');
        Word::query()->upsert($words, ['word', 'type'], ['vector']);
        $this->info('Данные вставлены, локальный словарь очищен');
    }

    private function getType(string $word, string $posType): ?string
    {
        if (isset(WordTypeFromPos::TYPES[$posType])) {
            return WordTypeFromPos::TYPES[$posType];
        } else {
            $partOfSpeech = $this->morphy->getPartOfSpeech(mb_strtoupper($word));

            if ($partOfSpeech) {
                $types = [
                    'С' => 'сущ',
                    'П' => 'прл',
                    'КР_ПРИЛ' => 'прл',
                    'ИНФИНИТИВ' => 'гл',
                    'Г' => 'гл',
                    'ДЕЕПРИЧАСТИЕ' => 'дееп',
                    'ПРИЧАСТИЕ' => 'прч',
                    'КР_ПРИЧАСТИЕ' => 'прч',
                    'ЧИСЛ' => 'числ',
                    'ЧИСЛ-П' => 'числ',
                    'МС' => 'мест',
                    'МС-ПРЕДК' => 'предик',
                    'МС-П' => 'прл',
                    'Н' => 'нар',
                    'ПРЕДК' => 'предик',
                    'ПРЕДЛ' => 'предл',
                    'СОЮЗ' => 'союз',
                    'МЕЖД' => 'межд',
                    'ЧАСТ' => 'част',
                    'ВВОДН' => 'ввод',
                    'ФРАЗ' => 'фраз',
                ];

                return $types[$partOfSpeech[0]] ?? null;
            }

            return null;
        }
    }
}
