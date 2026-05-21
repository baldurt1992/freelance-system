<?php

declare(strict_types=1);

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;

final class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_amount_cents' => ['required', 'integer', 'min:0'],
            // tax_rate is set at quote level from tenant settings; not accepted per line
            'lines.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'title' => 'título',
            'notes' => 'notas',
            'valid_until' => 'válida hasta',
            'lines' => 'líneas',
            'lines.*.description' => 'descripción',
            'lines.*.quantity' => 'cantidad',
            'lines.*.unit_amount_cents' => 'valor unitario',

            'lines.*.sort_order' => 'orden',
        ];
    }
}
