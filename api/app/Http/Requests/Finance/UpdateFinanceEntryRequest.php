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
            'amount_cents' => ['sometimes', 'integer', 'min:1'],
            'occurred_on' => ['sometimes', 'date_format:Y-m-d'],
            'description' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ];
    }
}
