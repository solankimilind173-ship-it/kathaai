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
                'mimetypes:text/csv,text/plain,application/csv',
                'max:10240', // 10 MiB
                new CsvUserImportStructure(1000),
            ],
        ];
    }
}
