<?php

namespace App\Drivers;

class Fb2Driver extends DictionaryDriver
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;

        parent::__construct();
    }

    public function getDictionary(): \Illuminate\Support\Collection
    {
        $file = fopen($this->filePath, 'r');

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
                $dictionary = $this->setWordsFromRow($row, $dictionary);
            }
        }

        return collect($dictionary)->sortDesc();
    }
}