<?php

namespace App\Jobs;

use App\Models\Book;
use App\Services\BookRecsService;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InitBookRecsJob extends Job
{
    private BookRecsService $bookRecommendationsService;

    private ?int $start;
    private ?int $end;

    public function __construct(int $start = null, int $end = null, bool $debug = false)
    {
        $this->start = $start;
        $this->end = $end;

        parent::__construct($debug);
    }

    public function handle()
    {
        $this->log('Начинается наполнение рекомендательной базы книг');

        $this->bookRecommendationsService = app(BookRecsService::class, ['debug' => $this->debugMode]);

        DB::statement("SET SESSION MAX_EXECUTION_TIME=86400000"); // 1 день

        $builder = DB::table('books')->orderBy('id')->where('status', Book::ACTIVE_STATUS);

        if ($this->start) {
            $builder->where('id', '>=', $this->start);
        }

        if ($this->end) {
            $builder->where('id', '<=', $this->end);
        }

        $builder->chunk(1000, function (Collection $data) {
            $bookIds = $data->pluck('id')->toArray();
            $this->bookRecommendationsService->createForBooks($bookIds);
        });

        $this->log('Наполнение рекомендательной базы книг завершено');

        $this->log('Инициализация наполнения book_recs_short');
        dispatch(new InitBookRecsShortJob($this->start, $this->end, $this->debugMode));
        $this->log('InitBookRecsShortJob отправлена в очередь');

        $message = "Наполнение рекомендательной базы книг завершено. \n";
        $message .= "Старт: " . $this->start ?? "Не указан" . "\n";
        $message .= "Конец: " . $this->end ?? "Не указан" . "\n";
        app(NotificationService::class)->notify($message);
    }
}
