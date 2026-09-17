<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cartón de pagos — Préstamo #{{ $prestamo->id }}</title>
    <style>
        @page { margin: 20px 26px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #1f2937; }

        .header { background: #0073c3; color: #fff; padding: 14px 18px; border-radius: 6px; margin-bottom: 14px; }
        .header .marca { font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; opacity: .85; }
        .header h1 { font-size: 17px; margin: 2px 0 0; }
        .header .sub { font-size: 10px; margin-top: 2px; opacity: .9; }

        .grid { display: table; width: 100%; margin-bottom: 14px; }
        .col { display: table-cell; width: 50%; vertical-align: top; padding-right: 12px; }
        .col:last-child { padding-right: 0; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; }
        .box h2 { font-size: 9px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin: 0 0 6px; }
        .box p { margin: 0 0 3px; }
        .box .label { color: #6b7280; }

        .estado { display: inline-block; padding: 1px 7px; border-radius: 10px; font-size: 9px; font-weight: 600; }
        .estado-success { background: #d1fae5; color: #047857; }
        .estado-warning { background: #fef3c7; color: #92400e; }
        .estado-danger { background: #fee2e2; color: #b91c1c; }
        .estado-info { background: #e0f2fe; color: #075985; }

        table.cuotas { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.cuotas th, table.cuotas td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; }
        table.cuotas th { background: #0073c3; color: #fff; font-weight: 600; }
        table.cuotas tr:nth-child(even) td { background: #f9fafb; }
        table.cuotas td.num { text-align: right; }
        table.cuotas tr.pagada td { color: #6b7280; }
        table.cuotas td.vencida { color: #b91c1c; font-weight: 600; }

        .footer { margin-top: 18px; display: table; width: 100%; }
        .footer .fecha { display: table-cell; font-size: 9px; color: #6b7280; vertical-align: bottom; }
        .footer .firma { display: table-cell; text-align: right; }
        .firma .linea { display: inline-block; width: 200px; border-top: 1px solid #9ca3af; margin-top: 24px; padding-top: 3px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="marca">GilenSoft</div>
        <h1>Cartón de pagos</h1>
        <div class="sub">Préstamo #{{ $prestamo->id }} · Emitido el {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <div class="grid">
        <div class="col">
            <div class="box">
                <h2>Cliente</h2>
                <p><strong>{{ $cliente?->full_name ?? '—' }}</strong></p>
                <p><span class="label">Documento:</span> {{ $cliente?->full_document ?? '—' }}</p>
                <p><span class="label">Dirección:</span> {{ $cliente?->direccion ?? '—' }}</p>
                <p><span class="label">Teléfono:</span> {{ $cliente?->telefono ?? '—' }}</p>
            </div>
        </div>
        <div class="col">
            <div class="box">
                <h2>Préstamo</h2>
                <p><span class="label">Monto prestado:</span> {{ number_format((float) $prestamo->monto_prestamo, 0, ',', '.') }}</p>
                <p><span class="label">Total a pagar:</span> {{ number_format((float) $prestamo->total, 0, ',', '.') }}</p>
                <p><span class="label">Registrado:</span> {{ \Carbon\Carbon::parse($prestamo->created_at)->format('d/m/Y') }} · <span class="label">Vence:</span> {{ \Carbon\Carbon::parse($prestamo->date_last_pay)->format('d/m/Y') }}</p>
                <p>
                    <span class="label">Estado:</span>
                    <span class="estado estado-{{ $prestamo->p_estatus?->type_tag?->type ?? 'info' }}">
                        {{ $prestamo->p_estatus?->description ?? '—' }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    <table class="cuotas">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cuota</th>
                <th>Estado</th>
                <th>Resta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cuotas as $c)
                <tr class="{{ $c['estado'] === 'Pagada' ? 'pagada' : '' }}">
                    <td>{{ $c['fecha'] }}</td>
                    <td class="num">{{ $c['aplica'] ? number_format($c['cuota'], 0, ',', '.') : '—' }}</td>
                    <td class="{{ $c['estado'] === 'Vencida' ? 'vencida' : '' }}">{{ $c['estado'] }}</td>
                    <td class="num">{{ $c['resta'] !== null ? number_format($c['resta'], 0, ',', '.') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Este préstamo no tiene cuotas registradas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="fecha">Generado el {{ now()->format('Y-m-d H:i') }} — sisconpre-v2</div>
        <div class="firma"><span class="linea">Firma / sello</span></div>
    </div>
</body>
</html>
