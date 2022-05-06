<?php

namespace App\Listeners;

use App\Events\BookDescriptionFrequencyCreated;
use App\Managers\BookVectorManager;
use App\Services\VectorService;

class GenerateBookDescriptionVector extends Listener
{
    private VectorService $vectorService;
    private BookVectorManager $vectorManager;

    public function __construct(VectorService $vectorService, BookVectorManager $vectorManager)
    {
        $this->vectorService = $vectorService;
        $this->vectorManager = $vectorManager;
    }

    public function handle(BookDescriptionFrequencyCreated $event)
    {
        /** Генерируем вектор по описанию */
        $vector = $this->vectorService->createForBookByDescription($event->book);

        if (!$vector) {
            return;
        }

        /** Сохраняем его в БД */
        $this->vectorManager->createOrUpdateDescription($event->book, $vector);
    }
}
