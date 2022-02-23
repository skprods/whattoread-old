<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @OA\RequestBody(
 *     request="BookUpdateRequest",
 *     required=true,
 *     description="Запрос на обновление книги",
 *     @OA\JsonContent(
 *         required={"title", "author", "status"},
 *         @OA\Property( property="title", type="string", example="Заголовок книги" ),
 *         @OA\Property( property="description", type="string", example="Описание книги" ),
 *         @OA\Property( property="author", type="string", example="Автор книги" ),
 *         @OA\Property( property="status", type="string", example="Статус книги" ),
 *         @OA\Property( property="genres", type="array", @OA\Items( type="integer" ), example={1,2,3,4} ),
 *     )
 * )
 */
class BookUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in(Book::STATUSES)],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['numeric', 'exists:genres,id'],
        ];
    }
}
