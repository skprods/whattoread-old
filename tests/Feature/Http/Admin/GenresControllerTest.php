<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Genre;
use Tests\TestCase;

class GenresControllerTest extends TestCase
{
    public function testIndex()
    {
        $user = $this->createAdminUser();
        Genre::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/api/v1/admin/genres');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                "*" => [
                    'id',
                    'name',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }
}
