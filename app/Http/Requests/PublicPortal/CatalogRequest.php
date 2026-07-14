<?php

namespace App\Http\Requests\PublicPortal;

use App\Enums\BookVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'collection' => ['nullable', 'integer', 'exists:collections,id'],
            'author' => ['nullable', 'integer', 'exists:authors,id'],
            'publisher' => ['nullable', 'integer', 'exists:publishers,id'],
            'language' => ['nullable', 'integer', 'exists:languages,id'],
            'year_from' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 5)],
            'year_to' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 5), 'gte:year_from'],
            'publication_type' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', Rule::in([
                BookVisibility::Public->value,
                BookVisibility::Role->value,
                BookVisibility::VerifiedEmail->value,
            ])],
            'sort' => ['nullable', Rule::in(['custom', 'newest', 'oldest', 'title_asc', 'title_desc', 'popular', 'downloaded'])],
            'mode' => ['nullable', Rule::in(['grid', 'list', 'shelf'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
