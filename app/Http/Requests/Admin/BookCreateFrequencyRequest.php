<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BookCreateFrequencyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'file' => ['required', 'file'],
        ];
    }
}
