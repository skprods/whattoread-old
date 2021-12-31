<?php

namespace App\Jobs\Vectors;

use App\Enums\Vectors;
use App\Jobs\QueueJob;
use App\Models\BookFrequencies\BookDescriptionFrequency;
use App\Services\VectorService;
use Illuminate\Support\Facades\DB;

class InitBookDescriptionVectorsJob extends QueueJob
{
    private string $table;
    private VectorService $vectorService;

    public function __construct(bool $debug)
    {
        /** @var BookDescriptionFrequency $frequency */
        $frequency = app(BookDescriptionFrequency::class);
        $this->table = $frequency->getTable();

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->vectorService = app(VectorService::class);

        $this->log('Начинается составление векторов книг (по описанию)');
        $words = DB::table($this->table)->selectRaw('distinct book_id')->orderBy('book_id')->get();
        $this->log('Идентификаторы слов получены');

        $words->pluck('word_id')->each(function (int $wordId) {
            $this->vectorService->createForWord($wordId, Vectors::DESCRIPTION_TYPE);
            $this->log("Вектор книги #{$wordId} составлен");
        });

        $this->log("Векторы составлены для всех слов из частотного словника");
    }
}
