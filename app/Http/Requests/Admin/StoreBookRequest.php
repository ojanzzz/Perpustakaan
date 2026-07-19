<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'editor' => ['nullable', 'string', 'max:255'],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'language_id' => ['nullable', 'exists:languages,id'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 5)],
            'publication_date' => ['nullable', 'date'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'publication_type' => ['nullable', 'string', 'max:100'],
            'visibility' => ['required', Rule::enum(BookVisibility::class)],
            'download_enabled' => ['sometimes', 'boolean'],
            'print_enabled' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => ['integer', 'exists:collections,id'],
            'author_ids' => ['sometimes', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:'.config('pdf.max_upload_kb')],
            'pdf_url' => ['nullable', 'url:http,https', 'max:2048', 'required_without:pdf'],
        ];
    }
}
