<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateFinanceEntryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:income,expense'],
            'amount_cents' => ['sometimes', 'integer', 'min:1'],
            'occurred_on' => ['sometimes', 'date_format:Y-m-d'],
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
        ];
    }
}
