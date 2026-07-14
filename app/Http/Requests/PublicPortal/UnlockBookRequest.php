<?php

namespace App\Http\Requests\PublicPortal;

use Illuminate\Foundation\Http\FormRequest;

class UnlockBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:255']];
    }
}
