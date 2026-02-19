<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'plan_id' => ['nullable', 'exists:plans,id'],
            'credits' => ['nullable', 'integer', 'min:0'],
            'role' => ['nullable', 'string', 'in:user,admin,super_admin'],
            'suspended_at' => ['nullable', 'boolean'],
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
