<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProjectRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'type' => ['required', 'string', 'in:freelance,fixed,retainer'],
            'agreed_total_cents' => ['required', 'integer', 'min:1'],
            'started_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'name' => 'nombre',
            'notes' => 'notas',
            'type' => 'tipo',
            'agreed_total_cents' => 'total acordado',
            'started_at' => 'fecha de inicio',
        ];
    }
}
