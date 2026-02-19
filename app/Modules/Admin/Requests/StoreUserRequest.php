<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'credits' => ['nullable', 'integer', 'min:0'],
            'role' => ['nullable', 'string', 'in:user,admin,super_admin'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->has('name') ? trim(strip_tags((string) $this->name)) : '',
            'email' => $this->has('email') ? trim(strip_tags((string) $this->email)) : '',
        ]);
    }
}
