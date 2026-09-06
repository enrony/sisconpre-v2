@php
    $prestamo = $prestamoDia->Prestamo;
    $cliente = $prestamo?->datoCliente;
    $cuotaOriginal = $prestamoDia->cuota / (1 + ($prestamoDia->surcharge / 100));
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Notificación de recargo</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color:#1f2937; line-height:1.5;">
    <h2 style="color:#b91c1c;">Notificación de recargo por mora</h2>

    <p>Estimado(a) {{ $cliente?->nombre }} {{ $cliente?->apellido }},</p>

    <p>Le informamos que se ha aplicado un recargo a su préstamo
        <strong>N.º {{ $prestamo?->id }}</strong>.</p>

    <p><strong>Detalle del recargo:</strong></p>
    <ul>
        <li>Cuota original: ${{ number_format($cuotaOriginal, 2) }}</li>
        <li>Recargo: {{ $prestamoDia->surcharge }}%</li>
        <li>Cuota con recargo: ${{ number_format($prestamoDia->cuota, 2) }}</li>
        @if ($prestamoDia->day_apply_surcharge)
            <li>Fecha de aplicación:
                {{ \Illuminate\Support\Carbon::parse($prestamoDia->day_apply_surcharge)->format('d/m/Y') }}</li>
        @endif
    </ul>

    <p>Le recomendamos realizar el pago lo antes posible para evitar cargos adicionales.</p>

    <p>Gracias por su atención.<br>{{ config('app.name') }}</p>
</body>
</html>
