<?php

namespace Tests\Feature\Http\User;

use App\Models\User;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    public function testInfo()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get("/api/v1/user");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'username',
                'email',
                'first_name',
                'last_name',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);
    }
}
