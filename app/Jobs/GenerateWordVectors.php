<?php

namespace App\Jobs;

use App\Models\Word;
use App\Services\VectorService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GenerateWordVectors extends Job
{
    private VectorService $vectorService;

    private array $data = [];

    public function handle()
    {
        $this->vectorService = app(VectorService::class);
        $count = 0;

        Word::query()
            ->whereNull('vector')
            ->chunk(1000, function (Collection $data) use (&$count) {
                $this->log("Получена коллекция слов. Смещение: $count");

                $data->each(function (Word $word) use (&$count) {
                    $vector = $this->vectorService->createForWord($word);
                    $this->data[] = [
                        'id' => $word->id,
                        'vector' => json_encode($vector),
                    ];

                    $count++;
                });

                $this->save();
            });
    }

    private function save()
    {
        $this->log("Сохраняем данные в БД...");
        $ids = [];

        $table = (new Word())->getTable();
        $query = "UPDATE $table SET vector = CASE ";

        foreach ($this->data as $word) {
            $vector = str_replace("\\n", '', $word['vector']);
            $query .= "WHEN id = {$word['id']} THEN '$vector' ";
            $ids[] = $word['id'];
        }

        $ids = implode(',', $ids);
        $query .= "END WHERE id IN ($ids)";

        try {
            DB::update($query);
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }

        $this->log("Данные сохранены");
        $this->data = [];
    }
}
