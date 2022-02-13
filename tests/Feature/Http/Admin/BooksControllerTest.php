<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BooksControllerTest extends TestCase
{
    private array $bookStructure = [
        'id',
        'title',
        'description',
        'author',
        'status',
        'genres',
        'words_count',
        'therm_frequencies',
        'created_at',
        'updated_at'
    ];

    public function testIndex()
    {
        $user = $this->createAdminUser();
        Book::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/api/v1/admin/books');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ["*" => $this->bookStructure],
            'pagination' => $this->paginationStructure,
        ]);
    }

    public function testIndexWithActiveStatus()
    {
        $user = $this->createAdminUser();
        Book::factory()->count(10)->create();
        Book::factory()->count(5)->create(['status' => "active"]);

        $response = $this->actingAs($user)->sendDataTableRequest('/api/v1/admin/books', [
            [
                'data' => 'Статус',
                'name' => 'status',
                'orderable' => true,
                'searchable' => true,
                'search' => [
                    'value' => 'active',
                    'regex' => '',
                ],
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ["*" => $this->bookStructure],
            'pagination' => $this->paginationStructure,
        ]);
        $response->assertJsonCount(5, 'data');
    }

    public function testIndexWithModerationStatus()
    {
        $user = $this->createAdminUser();
        Book::factory()->count(5)->create();
        Book::factory()->count(10)->create(['status' => "active"]);

        $response = $this->actingAs($user)->sendDataTableRequest('/api/v1/admin/books', [
            [
                'data' => 'Статус',
                'name' => 'status',
                'orderable' => true,
                'searchable' => true,
                'search' => [
                    'value' => 'moderation',
                    'regex' => '',
                ],
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => ["*" => $this->bookStructure],
            'pagination' => $this->paginationStructure,
        ]);
        $response->assertJsonCount(5, 'data');
    }

    public function testShow()
    {
        $user = $this->createAdminUser();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get("/api/v1/admin/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => $this->bookStructure,
        ]);
    }

    public function testCreate()
    {
        Event::fake();

        $user = $this->createAdminUser();
        $genres = Genre::factory()->count(5)->create();
        $data = [
            'title' => "Test Title",
            'description' => "Test Description",
            'author' => "Test Author",
            'status' => "active",
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->post('/api/v1/admin/books', $data);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => $this->bookStructure,
        ]);
        unset($data['genres']);
        $response->assertJsonFragment($data);
        $this->assertDatabaseHas('books', $data);

        $genres = $genres->map(function (Genre $genre) {
            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'status' => $genre->status,
                'created_at' => $genre->created_at->format("Y-m-d H:i:s"),
                'updated_at' => $genre->updated_at->format("Y-m-d H:i:s"),
            ];
        })->toArray();
        $response->assertJsonPath('data.genres', $genres);
    }

    public function testUpdate()
    {
        Event::fake();

        $user = $this->createAdminUser();
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(5)->create();
        $data = [
            'title' => "Test Title",
            'description' => "Test Description",
            'author' => "Test Author",
            'status' => "active",
            'genres' => $genres->pluck('id')->toArray(),
        ];

        $response = $this->actingAs($user)->put("/api/v1/admin/books/{$book->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => $this->bookStructure,
        ]);
        unset($data['genres']);
        $response->assertJsonFragment($data);
        $this->assertDatabaseHas('books', $data);

        $genres = $genres->map(function (Genre $genre) {
            return [
                'id' => $genre->id,
                'name' => $genre->name,
                'status' => $genre->status,
                'created_at' => $genre->created_at->format("Y-m-d H:i:s"),
                'updated_at' => $genre->updated_at->format("Y-m-d H:i:s"),
            ];
        })->toArray();
        $response->assertJsonPath('data.genres', $genres);
    }

    public function testDelete()
    {
        Event::fake();

        $user = $this->createAdminUser();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->delete("/api/v1/admin/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function testCreateFrequency()
    {
        Event::fake();
        Storage::fake('local');

        $user = $this->createAdminUser();
        $book = Book::factory()->create();
        $file = UploadedFile::fake()->create("book.fb2", 1000, 'text/xml');

        $response = $this->actingAs($user)->post("/api/v1/admin/books/{$book->id}/frequency", [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'message',
            ],
        ]);
    }
}
