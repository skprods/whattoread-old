<?php

namespace App\Jobs;

use App\Models\BookFrequencies\BookDescriptionFrequency;
use App\Services\Database\VectorService;
use Illuminate\Support\Facades\DB;

class InitBookDescriptionWordVectorsJob extends QueueJob
{
    private string $table;
    private VectorService $vectorService;

    public function __construct(bool $debug)
    {
        /** @var BookDescriptionFrequency $frequency */
        $frequency = app(BookDescriptionFrequency::class);
        $this->table = $frequency->getTable();

        $this->vectorService = app(VectorService::class);

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->log('Начинается составление векторов слов (по описанию)');
        $words = DB::table($this->table)->selectRaw('distinct word_id')->orderBy('word_id')->get();
        $this->log('Идентификаторы слов получены');

        $words->pluck('word_id')->each(function (int $wordId) {
            $this->vectorService->createForWord($wordId);
            $this->log("Вектор слова #{$wordId} составлен");
        });

        $this->log("Векторы составлены для всех слов из частотного словника");
    }
}
