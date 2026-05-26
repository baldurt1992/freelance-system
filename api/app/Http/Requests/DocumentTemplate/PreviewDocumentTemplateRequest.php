<?php

declare(strict_types=1);

namespace App\Http\Requests\DocumentTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PreviewDocumentTemplateRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['quote', 'billing'])],
            'html_body' => ['nullable', 'string', 'min:1', 'required_without:template_id'],
            'template_id' => ['nullable', 'integer', 'min:1', 'required_without:html_body'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipo',
            'html_body' => 'contenido HTML',
            'template_id' => 'plantilla',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'html_body.required_without' => 'Debe enviar contenido HTML o una plantilla.',
            'template_id.required_without' => 'Debe enviar una plantilla o contenido HTML.',
        ];
    }
}
