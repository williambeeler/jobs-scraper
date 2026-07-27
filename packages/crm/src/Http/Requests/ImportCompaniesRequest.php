<?php

namespace WilliamBeeler\Crm\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use WilliamBeeler\Crm\Support\TableNames;

class ImportCompaniesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:text/plain,text/csv,text/comma-separated-values,application/csv,application/vnd.ms-excel'],
            'tags' => ['nullable'],
            'campaigns' => ['nullable'],
            'source' => ['nullable', 'string', 'max:100'],
            'status_id' => ['required', 'integer', 'exists:'.TableNames::get('statuses').',id'],
            'relationship_id' => ['required', 'integer', 'exists:'.TableNames::get('relationships').',id'],
        ];
    }
}
