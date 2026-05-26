<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Application\Documents\PdfRenderer;
use App\Application\Documents\TemplateRenderer;
use App\Application\Documents\TemplateResolver;
use App\Application\Documents\TemplateVariableBuilder;
use App\Models\Quote;

final class QuotePdfGenerator
{
    public function __construct(
        private readonly TemplateResolver $templateResolver,
        private readonly TemplateVariableBuilder $variableBuilder,
        private readonly TemplateRenderer $templateRenderer,
        private readonly PdfRenderer $pdfRenderer,
    ) {}

    public function generate(Quote $quote): string
    {
        $quote->loadMissing('lines');

        $template = $this->templateResolver->resolve('quote', $quote->client_id);
        $variables = $this->variableBuilder->forQuote($quote);
        $html = $this->templateRenderer->render($template->html_body, $variables);

        return $this->pdfRenderer->renderHtmlToPdf($html);
    }
}
