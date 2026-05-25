<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:freelance,fixed,retainer'],
            'status' => ['sometimes', 'string', 'in:active,on_hold,completed,cancelled'],
            'started_at' => ['nullable', 'date_format:Y-m-d'],
            'completed_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'status' => 'estado',
            'started_at' => 'fecha de inicio',
            'completed_at' => 'fecha de completado',
        ];
    }
}
