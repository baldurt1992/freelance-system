<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuenta de cobro {{ $document->number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; margin: 40px; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        .meta { margin-bottom: 24px; color: #666; font-size: 12px; }
        .section { margin-bottom: 24px; }
        .section h2 { font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 40%; }
        .totals { margin-top: 16px; width: 300px; margin-left: auto; }
        .totals td { padding: 6px 8px; border: none; }
        .totals .label { text-align: left; }
        .totals .value { text-align: right; font-weight: bold; }
        .grand-total { font-size: 16px; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <h1>Cuenta de cobro {{ $document->number }}</h1>
    <div class="meta">
        Proyecto: {{ $document->project_name }}<br>
        @if($document->issued_at)
        Fecha de emisión: {{ $document->issued_at->format('Y-m-d') }}<br>
        @endif
    </div>

    <div class="section">
        <h2>Cliente</h2>
        <strong>{{ $document->client_name }}</strong><br>
        @if($document->client_email) {{ $document->client_email }}<br> @endif
        @if($document->client_tax_id) NIT / CC: {{ $document->client_tax_id }}<br> @endif
        @if($document->client_address) {{ $document->client_address }}<br> @endif
    </div>

    <div class="section">
        <h2>Resumen de cobro</h2>
        <table class="totals">
            <tr>
                <td class="label">Total acordado</td>
                <td class="value">{{ number_format($document->agreed_total_cents / 100, 2) }} {{ $document->currency }}</td>
            </tr>
            <tr>
                <td class="label">Cobrado</td>
                <td class="value">{{ number_format($document->paid_total_cents / 100, 2) }} {{ $document->currency }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Por cobrar</td>
                <td class="value">{{ number_format($document->balance_due_cents / 100, 2) }} {{ $document->currency }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
