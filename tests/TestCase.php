<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;

    public bool $seed = true;

    protected array $paginationStructure = [
        'per_page',
        'total',
        'current_page',
        'first_page',
        'last_page',
        'prev_page',
        'next_page',
    ];

    public function actingAs(UserContract $user, $guard = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer $token");

        return $this;
    }

    protected function createUser(array $params): User
    {
        return User::factory()->create($params);
    }

    protected function createAdminUser(array $params = []): User
    {
        $user = User::factory()->create($params);
        $user->assignRole('admin');

        return $user;
    }

    protected function sendDataTableRequest(string $url, array $columns, int $page = 1): TestResponse
    {
        $url .= "?page=$page";
        foreach ($columns as $key => $data) {
            foreach ($data as $columnKey => $value) {
                if (is_array($value)) {
                    foreach ($value as $valueKey => $item) {
                        $url .= "&columns[$key][$columnKey][$valueKey]=$item";
                    }
                } else {
                    if (is_bool($value)) {
                        $value = $value ? "true" : "false";
                    }

                    $url .= "&columns[$key][$columnKey]=$value";
                }
            }
        }

        return $this->get($url);
    }
}
