<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $quote->number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 14px; color: #333; margin: 40px; }
        h1 { font-size: 24px; margin-bottom: 8px; }
        .meta { margin-bottom: 24px; color: #666; font-size: 12px; }
        .section { margin-bottom: 24px; }
        .section h2 { font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .right { text-align: right; }
        .totals { margin-top: 16px; width: 300px; margin-left: auto; }
        .totals td { padding: 6px 8px; border: none; }
        .totals .label { text-align: left; }
        .totals .value { text-align: right; font-weight: bold; }
        .grand-total { font-size: 16px; border-top: 2px solid #333; }
    </style>
</head>
<body>
    <h1>Cotización {{ $quote->number }}</h1>
    <div class="meta">
        Estado: {{ ucfirst($quote->status) }}<br>
        Fecha: {{ $quote->created_at->format('Y-m-d') }}<br>
        @if($quote->valid_until)
        Válida hasta: {{ $quote->valid_until->format('Y-m-d') }}
        @endif
    </div>

    <div class="section">
        <h2>Cliente</h2>
        <strong>{{ $quote->client_name }}</strong><br>
        @if($quote->client_email) {{ $quote->client_email }}<br> @endif
        @if($quote->client_tax_id) NIT / CC: {{ $quote->client_tax_id }}<br> @endif
        @if($quote->client_address) {{ $quote->client_address }}<br> @endif
    </div>

    @if($quote->title || $quote->notes)
    <div class="section">
        @if($quote->title) <strong>{{ $quote->title }}</strong><br> @endif
        @if($quote->notes) {{ $quote->notes }}<br> @endif
    </div>
    @endif

    <div class="section">
        <h2>Detalle</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th class="right">Cantidad</th>
                    <th class="right">Valor unitario</th>
                    <th class="right">Total línea</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ number_format($line->unit_amount_cents / 100, 2) }} {{ $quote->currency }}</td>
                    <td class="right">{{ number_format($line->line_total_cents / 100, 2) }} {{ $quote->currency }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">{{ number_format($quote->subtotal_cents / 100, 2) }} {{ $quote->currency }}</td>
            </tr>
            <tr>
                <td class="label">Impuestos</td>
                <td class="value">{{ number_format($quote->tax_cents / 100, 2) }} {{ $quote->currency }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label">Total</td>
                <td class="value">{{ number_format($quote->total_cents / 100, 2) }} {{ $quote->currency }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
