<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->max(20)->mixedCase()->numbers()->symbols()],
            'name'     => ['required', 'string', 'min:3', 'max:50', 'regex:/^[\pL\s]+$/u'],
        ];
    }
}
