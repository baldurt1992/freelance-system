<?php

declare(strict_types=1);

namespace App\Application\Documents;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfRenderer
{
    public function renderHtmlToPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
