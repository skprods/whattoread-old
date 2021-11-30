<?php

namespace App\Http\Requests\Admin;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
