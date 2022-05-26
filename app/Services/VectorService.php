<?php

namespace App\Services;

use App\Clients\RusVectoresClient;
use App\Models\Book;
use App\Models\BookDescriptionFrequency;
use App\Models\Word;
use cijic\phpMorphy\Morphy;
use Illuminate\Support\Collection;

/**
 * Сервис для работы с векторами
 */
class VectorService
{
    /** Фасад для работы с морфологией */
    private Morphy $morphy;
    /** Клиент сайта ResVectores для определения близких слов */
    private RusVectoresClient $client;

    /** Длина вектора */
    private int $vectorLength;
    /** Минимальное значение вершины вектора */
    private int $vectorMin;
    /** Максимальное значение вершины вектора */
    private int $vectorMax;

    /** Созданный вектор */
    private array $vector;

    /** Множитель (для генерации значений) */
    public static int $accuracy = 1000000000;
    /** Точность (для значений вершин вектора */
    public static int $precision = 8;

    public function __construct(Morphy $morphy, RusVectoresClient $client)
    {
        $this->morphy = $morphy;
        $this->client = $client;

        $this->vectorLength = config('variables.vectors.length');
        $this->vectorMin = config('variables.vectors.min');
        $this->vectorMax = config('variables.vectors.max');
    }

    public function createForBookByDescription(Book $book): ?array
    {
        $this->vector = [];
        $frequencies = BookDescriptionFrequency::getBookFrequenciesByBookIds([$book->id]);

        if ($frequencies->count() === 0) {
            return null;
        }

        /**
         * В вектор книги войдут только первые 40 слов из
         * частотного словника, поэтому нужно обрезать
         * коллекцию до 40
         *
         * @var Collection $frequencies
         */
        $frequencies = $frequencies->first()->chunk(40)->first();

        /** Получаем слова с их векторами в формате wordId => vector */
        $vectors = Word::getByIds($frequencies->keys()->toArray())->pluck('vector', 'id');

        /**
         * Проходим по каждому слову из словника и формируем
         * его новый вектор, умноженный на частоту слова.
         * Для этого нужно каждую вершину умножить на частоту
         * слова в словнике этой книги.
         *
         * На выходе $this->vector будет состоять из 40 векторов
         * слов, умноженных на соответствующие частоты.
         */
        $frequencies->each(function ($frequency, $wordId) use ($vectors) {
            /** Берём вектор слова */
            $vector = $vectors->get($wordId);

            /** Получаем вектор слова с множителем */
            $wordVector = [];
            foreach ($vector as $value) {
                $multValue = $frequency * $value;
                $wordVector[] = round($multValue, self::$precision);
            }

            $this->vector[] = $wordVector;
        });

        /** Формируем однослойный вектор книги путём сложения всех векторов */
        $bookVector = [];
        foreach ($this->vector[0] as $key => $value) {
            $bookValue = 0;
            foreach ($this->vector as $wordVector) {
                $bookValue += $wordVector[$key];
            }

            $bookVector[$key] = round($bookValue, self::$precision);
        }

        $this->vector = $bookVector;

        return $this->vector;
    }

    /** Создание вектора для слова из БД */
    public function createForWord(Word $word): array
    {
        $this->vector = [];

        /** Пытаемся найти базовую форму слова и из неё вытащить вектор */
        if ($this->setByBaseForm($word) === true) {
            return $this->vector;
        }

        /** Запрашиваем похожее слово и ищем его вектор в БД */
        if ($this->setByRusVectors($word) === true) {
            return $this->vector;
        }

        /** Если предыдущие два метода не сработали, генерируем вектор сами */
        $this->vector = $this->generate($this->vectorLength, $this->vectorMin, $this->vectorMax);

        return $this->vector;
    }

    /** Поиск базовой формы слова и получение вектора из неё */
    private function setByBaseForm(Word $word): bool
    {
        $baseForm = $this->morphy->getBaseForm(mb_strtoupper($word->word));

        /** Найдена базовая форма слова */
        if ($baseForm !== false) {
            $baseForm = $baseForm[0];

            /** @var Word|null $baseWord */
            $baseWord = Word::query()
                ->where('word', mb_strtolower($baseForm))
                ->whereNotNull('vector')
                ->first();

            /** Если у базовой формы нет вектора, выходим из метода */
            if (!$baseWord) {
                return false;
            }

            /** Если найден, добавляем в него погрешность и сохраняем */
            $this->vector = $this->prepareWithInaccuracy($baseWord->vector);
            return true;
        } else {
            return false;
        }
    }

    /** Поиск похожего слова и получение вектора из него */
    private function setByRusVectors(Word $word): bool
    {
        /** Запрашиваем похожие слова у сайта RusVectores */
        $result = $this->client->getWordMatches($word->word);
        if (!$result) {
            return false;
        }

        $dictionary = array_shift($result);
        $matches = array_shift($dictionary);
        $matchWord = array_shift($matches);

        /** @var Word|null $dbWord */
        $dbWord = Word::query()
            ->where('word', $matchWord)
            ->whereNotNull('vector')
            ->first();

        /** Если у похожего слова нет вектора, выходим из метода */
        if (!$dbWord) {
            return false;
        }

        /** Если есть, добавляем в него погрешность и сохраняем */
        $this->vector = $this->prepareWithInaccuracy($dbWord->vector);
        return true;
    }

    /**
     * Генерирование вектора
     *
     * @param int $length   - длина вектора
     * @param int $min      - минимальное значение
     * @param int $max      - максимальное значение
     * @return float[]      - вектор
     */
    public function generate(int $length, int $min = 0, int $max = 100): array
    {
        $vector = [];

        for ($i = 1; $i <= $length; $i++) {
            $minValue = $min * self::$accuracy + 1 / self::$accuracy;
            $maxValue = $max * self::$accuracy - 1 / self::$accuracy;

            $vector[] = round(mt_rand($minValue, $maxValue) / self::$accuracy, self::$precision);
        }

        return $vector;
    }

    /**
     * Добавляет к переданному вектору погрешность,
     * чтобы сделать его уникальным
     *
     * @param array $vector         - вектор, который нужно изменить
     * @param int $minInaccuracy    - минимальное изменение вектора (будет поделено на 10^8)
     * @param int $maxInaccuracy    - максимальное изменение вектора (будет поделено на 10^8)
     * @return array
     */
    public function prepareWithInaccuracy(array $vector, int $minInaccuracy = 0, int $maxInaccuracy = 100000): array
    {
        $sign = rand(0, 1);

        foreach ($vector as $key => $item) {
            $value = round(mt_rand($minInaccuracy, $maxInaccuracy) / self::$accuracy, self::$precision);

            if ($sign) {
                $item += $value;
            } else {
                $item -= $value;
            }

            $vector[$key] = $item;
        }

        return $vector;
    }

    public static function subtract(array $first, array $second): array
    {
        $result = [];

        if (count($first) >= count($second)) {
            foreach ($first as $key => $value) {
                $result[$key] = $value - $second[$key] ?? 0;
            }
        } else {
            foreach ($second as $key => $value) {
                $result[$key] = - $value + $first[$key] ?? 0;
            }
        }

        return $result;
    }

    public static function hadamardMultiply(array ...$vectors): array
    {
        $result = [];
        $firstVector = array_shift($vectors);

        foreach ($firstVector as $key => $value) {
            $res = $value;

            foreach ($vectors as $vector) {
                $res = $res * $vector[$key];
            }

            $result[$key] = $res;
        }

        return $result;
    }

    /** Генерация вектора из 1 */
    public static function generateUnitsVector(int $length)
    {
        $result = [];

        for ($i = 0; $i < $length; $i++) {
            $result[] = 1;
        }

        return $result;
    }
}
