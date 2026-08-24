<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\Purpose;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWishlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int|string, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists(Category::class, 'id')->where('is_active', true),
            ],
            'priority' => ['sometimes', 'required', Rule::enum(Priority::class)],
            'purpose' => ['sometimes', 'required', Rule::enum(Purpose::class)],
            'estimated_price' => ['sometimes', 'required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
