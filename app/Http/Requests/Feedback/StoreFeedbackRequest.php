<?php

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'type' => ['required', Rule::in(['suggestion', 'report', 'contact'])],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'min:5', 'max:150', 'not_regex:/[<>]/'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
