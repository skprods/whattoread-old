<?php

namespace App\Jobs;

use App\Managers\BooksManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class ParseBukvoedJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private bool $debugMode;

    private Client $client;
    private BooksManager $manager;

    public function __construct(bool $debugMode = false)
    {
        $this->debugMode = $debugMode;
        $this->manager = app(BooksManager::class);
    }

    public function handle()
    {
        $this->client = new Client();

        $bookId = 400001;

        Console::info("Начинается парсинг интернет-магазина Буквоед.");

        while ($bookId < 15000000) {
            $this->addBook($bookId);
            sleep(10);

            if ($bookId % 10 === 0) {
                unset($this->manager);
                $this->manager = app(BooksManager::class);

                sleep(60);
            }

            $bookId++;
        }
    }

    public function addBook(int $bookId)
    {
        Console::info("Просматриваем книгу #$bookId...");
        $url = 'https://www.bookvoed.ru/book?id=' . $bookId;
        $request = new Request('GET', $url);

        $response = $this->client->send($request, ['http_errors' => false]);

        Console::info("#$bookId :: Ответ " . $response->getStatusCode());

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $content = $response->getBody()->getContents();

        Console::info("#$bookId :: Добавляем книгу в базу.");
        $result = $this->manager->addFromBukvoed($content, $url, $bookId);

        if ($result) {
            Console::info("#$bookId :: Книга добавлена");
        } else {
            Console::info("#$bookId :: Книга не добавлена!");
            Log::info("#$bookId :: Книга не добавлена!");
        }
    }
}
