<?php

namespace App\Http\Requests\Reader;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $pages = max(1, (int) $this->route('book')->page_count);

        return [
            'page' => ['required', 'integer', 'min:1', "max:{$pages}"],
            'duration_delta' => ['required', 'integer', 'min:0', 'max:300'],
        ];
    }
}
