<?php

declare(strict_types=1);

namespace App\Application\Documents;

use App\Models\BillingDocument;
use App\Models\Quote;
use App\Support\Money\MoneyFormatter;

final class TemplateVariableBuilder
{
    /**
     * @return array<string, string>
     */
    public function forQuote(Quote $quote): array
    {
        $quote->loadMissing('lines');

        $linesHtml = $this->buildQuoteLinesTable($quote);

        $validUntilBlock = $quote->valid_until !== null
            ? 'Válida hasta: ' . $quote->valid_until->format('Y-m-d') . '<br>'
            : '';

        $titleNotesBlock = '';
        if ($quote->title || $quote->notes) {
            $titleNotesBlock = '<div class="section">';
            if ($quote->title) {
                $titleNotesBlock .= '<strong>' . e($quote->title) . '</strong><br>';
            }
            if ($quote->notes) {
                $titleNotesBlock .= e($quote->notes) . '<br>';
            }
            $titleNotesBlock .= '</div>';
        }

        return [
            'document_number' => e($quote->number),
            'document_status' => e(ucfirst($quote->status)),
            'document_date' => $quote->created_at?->format('Y-m-d') ?? '',
            'valid_until_block' => $validUntilBlock,
            'client_name' => e($quote->client_name),
            'client_email_block' => $quote->client_email ? e($quote->client_email) . '<br>' : '',
            'client_tax_id_block' => $quote->client_tax_id ? 'NIT / CC: ' . e($quote->client_tax_id) . '<br>' : '',
            'client_address_block' => $quote->client_address ? e($quote->client_address) . '<br>' : '',
            'title_notes_block' => $titleNotesBlock,
            'lines_table' => $linesHtml,
            'subtotal_formatted' => MoneyFormatter::format($quote->subtotal_cents, $quote->currency),
            'tax_formatted' => MoneyFormatter::format($quote->tax_cents, $quote->currency),
            'total_formatted' => MoneyFormatter::format($quote->total_cents, $quote->currency),
            'currency' => e($quote->currency),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function forBilling(BillingDocument $document): array
    {
        $issuedAtBlock = $document->issued_at !== null
            ? 'Fecha de emisión: ' . $document->issued_at->format('Y-m-d') . '<br>'
            : '';

        return [
            'document_number' => e($document->number),
            'project_name' => e($document->project_name),
            'issued_at_block' => $issuedAtBlock,
            'client_name' => e($document->client_name),
            'client_email_block' => $document->client_email ? e($document->client_email) . '<br>' : '',
            'client_tax_id_block' => $document->client_tax_id ? 'NIT / CC: ' . e($document->client_tax_id) . '<br>' : '',
            'client_address_block' => $document->client_address ? e($document->client_address) . '<br>' : '',
            'agreed_total_formatted' => MoneyFormatter::format($document->agreed_total_cents, $document->currency),
            'paid_total_formatted' => MoneyFormatter::format($document->paid_total_cents, $document->currency),
            'balance_due_formatted' => MoneyFormatter::format($document->balance_due_cents, $document->currency),
            'currency' => e($document->currency),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function sampleForType(string $type): array
    {
        if ($type === 'billing') {
            return [
                'document_number' => 'CC-000001',
                'project_name' => 'Sitio web de ejemplo',
                'issued_at_block' => 'Fecha de emisión: ' . now()->toDateString() . '<br>',
                'client_name' => 'Cliente de ejemplo S.A.S.',
                'client_email_block' => 'cliente@ejemplo.test<br>',
                'client_tax_id_block' => 'NIT / CC: 900123456-7<br>',
                'client_address_block' => 'Calle 100 # 15-20<br>',
                'agreed_total_formatted' => MoneyFormatter::format(3_400_00, 'COP'),
                'paid_total_formatted' => MoneyFormatter::format(1_000_00, 'COP'),
                'balance_due_formatted' => MoneyFormatter::format(2_400_00, 'COP'),
                'currency' => 'COP',
            ];
        }

        return [
            'document_number' => 'Q-000001',
            'document_status' => 'Draft',
            'document_date' => now()->toDateString(),
            'valid_until_block' => 'Válida hasta: ' . now()->addDays(15)->toDateString() . '<br>',
            'client_name' => 'Cliente de ejemplo S.A.S.',
            'client_email_block' => 'cliente@ejemplo.test<br>',
            'client_tax_id_block' => 'NIT / CC: 900123456-7<br>',
            'client_address_block' => 'Calle 100 # 15-20<br>',
            'title_notes_block' => '<div class="section"><strong>Proyecto demo</strong><br>Alcance de ejemplo para vista previa.</div>',
            'lines_table' => $this->buildSampleQuoteLinesTable(),
            'subtotal_formatted' => MoneyFormatter::format(2_857_14, 'COP'),
            'tax_formatted' => MoneyFormatter::format(542_86, 'COP'),
            'total_formatted' => MoneyFormatter::format(3_400_00, 'COP'),
            'currency' => 'COP',
        ];
    }

    private function buildQuoteLinesTable(Quote $quote): string
    {
        $rows = '';
        $index = 1;

        foreach ($quote->lines as $line) {
            $unit = MoneyFormatter::format($line->unit_amount_cents, $quote->currency);
            $total = MoneyFormatter::format($line->line_total_cents, $quote->currency);

            $rows .= '<tr>'
                . '<td>' . $index . '</td>'
                . '<td>' . e($line->description) . '</td>'
                . '<td class="right">' . e((string) $line->quantity) . '</td>'
                . '<td class="right">' . $unit . '</td>'
                . '<td class="right">' . $total . '</td>'
                . '</tr>';
            $index++;
        }

        return '<table>'
            . '<thead><tr>'
            . '<th>#</th><th>Descripción</th>'
            . '<th class="right">Cantidad</th>'
            . '<th class="right">Valor unitario</th>'
            . '<th class="right">Total línea</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }

    private function buildSampleQuoteLinesTable(): string
    {
        $lines = [
            ['Diseño UI', '2', '500,00 COP', '1.000,00 COP'],
            ['Desarrollo frontend', '3', '800,00 COP', '2.400,00 COP'],
        ];

        $rows = '';
        $index = 1;

        foreach ($lines as [$desc, $qty, $unit, $total]) {
            $rows .= '<tr>'
                . '<td>' . $index . '</td>'
                . '<td>' . e($desc) . '</td>'
                . '<td class="right">' . e($qty) . '</td>'
                . '<td class="right">' . e($unit) . '</td>'
                . '<td class="right">' . e($total) . '</td>'
                . '</tr>';
            $index++;
        }

        return '<table>'
            . '<thead><tr>'
            . '<th>#</th><th>Descripción</th>'
            . '<th class="right">Cantidad</th>'
            . '<th class="right">Valor unitario</th>'
            . '<th class="right">Total línea</th>'
            . '</tr></thead>'
            . '<tbody>' . $rows . '</tbody>'
            . '</table>';
    }
}
