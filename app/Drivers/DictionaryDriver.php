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

    private array $utf8replace = [
        "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
        "\x08", "\x09", "\x0a", "\x0b", "\x0c", "\x0d", "\x0e", "\x0f",
        "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
        "\x18", "\x19", "\x1a", "\x1b", "\x1c", "\x1d", "\x1e", "\x1f",
    ];

    /** Симолы, которые необходимо заменить на пустую строку */
    private array $replacingSymbols;

    /** Символ неразрывного пробела, который часто встречается в тексте */
    private string $nbsp;

    public function __construct()
    {
        $replacingSymbolsFile = file_get_contents(resource_path("dictionary/replacingSymbols.json"));
        $this->replacingSymbols = json_decode($replacingSymbolsFile, true);
        $this->replacingSymbols = array_merge($this->replacingSymbols, $this->utf8replace);

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

        /** убираем теги и переводим в нижний регистр */
        $row = strip_tags($row);
        $row = mb_strtolower($row);

        /** удаляем запрещённые символы */
        $row = $this->deleteForbiddenSymbols($row);

        /** Убираем букву ё */
        $row = str_replace('ё', 'е', $row);

        return trim($row);
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