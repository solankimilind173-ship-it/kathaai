<?php

namespace App\Modules\Admin\Requests;

use App\Rules\CsvUserImportStructure;
use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
                new CsvUserImportStructure(1000),
            ],
        ];
    }
}
