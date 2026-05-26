<?php

declare(strict_types=1);

namespace App\Application\Documents;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DocumentTemplateService
{
    public function __construct(
        private readonly TemplateRenderer $templateRenderer,
        private readonly TemplateVariableBuilder $variableBuilder,
        private readonly PdfRenderer $pdfRenderer,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, DocumentTemplate>
     */
    public function list(?string $type = null)
    {
        $query = DocumentTemplate::query()->orderBy('type')->orderByDesc('is_default')->orderBy('name');

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        return $query->get();
    }

    public function find(int $id): ?DocumentTemplate
    {
        return DocumentTemplate::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(DocumentTemplate $template, array $data): DocumentTemplate
    {
        return DB::transaction(function () use ($template, $data) {
            if (isset($data['is_default']) && $data['is_default'] === true) {
                DocumentTemplate::query()
                    ->where('type', $template->type)
                    ->where('client_id', $template->client_id)
                    ->whereKeyNot($template->id)
                    ->update(['is_default' => false]);
            }

            $template->update($data);

            return $template->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function preview(array $payload): string
    {
        $type = (string) $payload['type'];

        if (isset($payload['html_body']) && is_string($payload['html_body'])) {
            $htmlBody = $payload['html_body'];
        } elseif (isset($payload['template_id'])) {
            $template = $this->find((int) $payload['template_id']);

            if ($template === null) {
                throw new NotFoundHttpException('Plantilla no encontrada.');
            }

            $htmlBody = $template->html_body;
        } else {
            throw new NotFoundHttpException('Debe enviar html_body o template_id.');
        }

        $variables = $this->variableBuilder->sampleForType($type);
        $html = $this->templateRenderer->render($htmlBody, $variables);

        return $this->pdfRenderer->renderHtmlToPdf($html);
    }
}
