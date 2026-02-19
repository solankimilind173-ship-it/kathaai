<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slugRule = Rule::unique('plans', 'slug');
        if ($this->route('plan')) {
            $slugRule->ignore($this->route('plan')->id);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'monthly_credits' => ['required', 'integer', 'min:0'],
            'credit_rollover' => ['boolean'],
            'is_active' => ['boolean'],
            'features' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->has('name') ? trim(strip_tags((string) $this->name)) : '',
            'slug' => $this->has('slug') ? trim(strip_tags((string) $this->slug)) : '',
        ]);
        if ($this->has('credit_rollover')) {
            $this->merge(['credit_rollover' => (bool) $this->credit_rollover]);
        }
        if ($this->has('is_active')) {
            $this->merge(['is_active' => (bool) $this->is_active]);
        }
    }
}
