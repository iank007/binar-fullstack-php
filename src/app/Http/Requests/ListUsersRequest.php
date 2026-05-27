<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'page'   => ['nullable', 'integer', 'min:1'],
            'sortBy' => ['nullable', 'string', 'in:name,email,created_at'],
        ];
    }
}
