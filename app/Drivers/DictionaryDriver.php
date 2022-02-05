<?php

namespace App\Drivers;

abstract class DictionaryDriver
{
    private array $ruAlphabet = [
        'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е', 'Ё', 'ё', 'Ж', 'ж', 'З', 'з',
        'И', 'и', 'Й', 'й', 'К', 'к', 'Л', 'л', 'М', 'м', 'Н', 'н', 'О', 'о', 'П', 'п', 'Р', 'р',
        'С', 'с', 'Т', 'т', 'У', 'у', 'Ф', 'ф', 'Х', 'х', 'Ц', 'ц', 'Ч', 'ч', 'Ш', 'ш', 'Щ', 'щ',
        'Ъ', 'ъ', 'Ы', 'ы', 'Ь', 'ь', 'Э', 'э', 'Ю', 'ю', 'Я', 'я'
    ];

    /** Симолы, которые необходимо заменить на пустую строку */
    private array $replacingSymbols;

    /** Символ неразрывного пробела, который часто встречается в тексте */
    private string $nbsp;

    public function __construct()
    {
        $replacingSymbolsFile = file_get_contents(resource_path("dictionary/replacingSymbols.json"));
        $this->replacingSymbols = json_decode($replacingSymbolsFile, true);

        $this->nbsp = html_entity_decode("&nbsp;");
    }

    abstract public function getDictionary();

    protected function setWordsFromRow(string $row, array $dictionary): array
    {
        /** Удаление ненужных символов и тегов */
        $row = $this->prepareRow($row);

        $words = preg_split('/ +/', $row);

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            if (isset($dictionary[$word])) {
                $dictionary[$word] += 1;
            } else {
                $dictionary[$word] = 1;
            }
        }

        return $dictionary;
    }

    /** Подготовка строки к разбивке на частотный словник */
    private function prepareRow(string $row): string
    {
        if ($row === '') {
            return $row;
        }

        /** убираем теги */
        $row = strip_tags($row);

        /** удаляем запрещённые символы */
        $row = $this->deleteForbiddenSymbols($row);

        return mb_strtolower(trim($row));
    }

    private function deleteForbiddenSymbols(string $row): string
    {
        /** Удаление nbsp-символа */
        $row = str_replace($this->nbsp, '', $row);

        /** Удалеине -, если он стоит не между двух симолов и всех символов, подлежащих замене */
        preg_match_all('/./u', $row, $rowSymbols);
        $rowSymbols = $rowSymbols[0];
        foreach ($rowSymbols as $key => $symbol) {
            $needDelete = false;

            if ($symbol === '-') {
                $symbolBefore = $rowSymbols[$key - 1] ?? null;
                $symbolAfter = $rowSymbols[$key + 1] ?? null;

                if (!in_array($symbolBefore, $this->ruAlphabet) || !in_array($symbolAfter, $this->ruAlphabet)) {
                    $needDelete = true;
                }
            }

            if (in_array($symbol, $this->replacingSymbols)) {
                $needDelete = true;
            }

            if ($needDelete) {
                unset($rowSymbols[$key]);
            }
        }

        return implode('', $rowSymbols);
    }
}