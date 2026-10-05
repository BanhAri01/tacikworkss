<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->input('slug') ?: $this->input('name', ''))]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:100', Rule::unique('categories', 'slug')->ignore($category)],
            'parent_id' => [
                'nullable', 'integer', Rule::exists('categories', 'id'),
                // Prevent cycles: a category can't move under itself or its descendants.
                Rule::notIn($category ? $category->descendantAndSelfIds() : []),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
