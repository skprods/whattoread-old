<?php

namespace App\Jobs;

use App\Managers\BooksManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SKprods\LaravelHelpers\Console;

class ParseSamolitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private Client $client;
    private BooksManager $manager;

    private int $start;
    private int $end;

    public function __construct(int $start, int $end)
    {
        $this->manager = app(BooksManager::class);

        $this->start = $start;
        $this->end = $end;
    }

    public function handle()
    {
        $this->client = new Client();

        $bookId = $this->start;
        Console::info("Начинается парсинг интернет-портала Самолит.");

        while ($bookId < $this->end) {
            $this->addBook($bookId);
            $bookId++;
        }
    }

    private function addBook(int $bookId)
    {
        Console::info("Просматриваем книгу #$bookId...");
        $url = "https://samolit.com/books/$bookId/";
        $request = new Request('GET', $url);

        $response = $this->client->send($request, ['http_errors' => false, 'verify' => false]);
        Console::info("#$bookId :: Ответ " . $response->getStatusCode());

        if ($response->getStatusCode() !== 200) {
            return;
        }

        Console::info("#$bookId :: Добавляем книгу в базу.");

        $content = $response->getBody()->getContents();
        $result = $this->manager->addFromSamolit($content);

        if ($result) {
            Console::info("#$bookId :: Книга добавлена");
            Log::info("#$bookId :: Книга добавлена");
        } else {
            Console::info("#$bookId :: Книга не добавлена!");
            Log::info("#$bookId :: Книга не добавлена!");
        }
    }
}
