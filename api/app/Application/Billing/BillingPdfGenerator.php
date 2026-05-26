<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Application\Documents\PdfRenderer;
use App\Application\Documents\TemplateRenderer;
use App\Application\Documents\TemplateResolver;
use App\Application\Documents\TemplateVariableBuilder;
use App\Models\BillingDocument;
use Illuminate\Support\Facades\Storage;

final class BillingPdfGenerator
{
    public function __construct(
        private readonly TemplateResolver $templateResolver,
        private readonly TemplateVariableBuilder $variableBuilder,
        private readonly TemplateRenderer $templateRenderer,
        private readonly PdfRenderer $pdfRenderer,
    ) {}

    public function generate(BillingDocument $document): string
    {
        $template = $this->templateResolver->resolve('billing', $document->client_id);
        $variables = $this->variableBuilder->forBilling($document);
        $html = $this->templateRenderer->render($template->html_body, $variables);

        return $this->pdfRenderer->renderHtmlToPdf($html);
    }

    public function store(BillingDocument $document): string
    {
        $pdfContent = $this->generate($document);
        $path = "billing/{$document->number}.pdf";

        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }
}
