<?php

declare(strict_types=1);

namespace App\Http\Requests\DocumentTemplate;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDocumentTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTenantOwner() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:255'],
            'html_body' => ['sometimes', 'string', 'min:1'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'html_body' => 'contenido HTML',
            'is_default' => 'predeterminada',
        ];
    }
}
