<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => mb_strtolower(trim((string) $this->input('name'))),
            'slug' => Str::slug((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'slug' => ['required', Rule::unique('tags', 'slug')->ignore($this->route('tag'))],
        ];
    }

    public function messages(): array
    {
        return ['slug.unique' => __('This tag already exists.')];
    }
}
