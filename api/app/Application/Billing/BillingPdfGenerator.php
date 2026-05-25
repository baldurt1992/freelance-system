<?php

declare(strict_types=1);

namespace App\Application\Billing;

use App\Models\BillingDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

final class BillingPdfGenerator
{
    public function generate(BillingDocument $document): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = View::make('billing.pdf', ['document' => $document])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function store(BillingDocument $document): string
    {
        $pdfContent = $this->generate($document);
        $path = "billing/{$document->number}.pdf";

        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }
}
