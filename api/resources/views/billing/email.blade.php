Cuenta de cobro {{ $document->number }}

Proyecto: {{ $document->project_name }}

Total acordado: {{ number_format($document->agreed_total_cents / 100, 2) }} {{ $document->currency }}
Cobrado: {{ number_format($document->paid_total_cents / 100, 2) }} {{ $document->currency }}
Por cobrar: {{ number_format($document->balance_due_cents / 100, 2) }} {{ $document->currency }}

Adjuntamos el PDF de la cuenta de cobro.
