<?php

declare(strict_types=1);

namespace App\Application\Quotes;

use App\Models\Quote;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\View;

final class QuotePdfGenerator
{
    public function generate(Quote $quote): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = View::make('quotes.pdf', ['quote' => $quote->load('lines')])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
