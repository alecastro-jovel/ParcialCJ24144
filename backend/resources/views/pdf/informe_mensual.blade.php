<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Informe mensual de constancias</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    h1 { font-size: 18px; color: #1565C0; margin-bottom: 0; }
    h2 { font-size: 13px; margin-top: 22px; border-bottom: 2px solid #1565C0; padding-bottom: 3px; }
    .meta { color: #666; margin-top: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #1565C0; color: #fff; padding: 5px; text-align: left; }
    td { border-bottom: 1px solid #ddd; padding: 4px 5px; }
    tr.total td { font-weight: bold; background: #E3F2FD; }
    .center { text-align: center; }
</style>
</head>
<body>
    <h1>Informe de constancias registradas por mes</h1>
    <div class="meta">Generado el {{ $generado }} por {{ $usuario }}</div>

    <h2>Resumen mensual</h2>
    <table>
        <thead>
            <tr><th>Mes</th><th class="center">Total registradas</th><th class="center">Activas</th><th class="center">Inactivas</th></tr>
        </thead>
        <tbody>
            @foreach ($resumen as $fila)
                <tr>
                    <td>{{ $fila['mes'] }}</td>
                    <td class="center">{{ $fila['total'] }}</td>
                    <td class="center">{{ $fila['activas'] }}</td>
                    <td class="center">{{ $fila['inactivas'] }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>TOTAL</td>
                <td class="center">{{ $resumen->sum('total') }}</td>
                <td class="center">{{ $resumen->sum('activas') }}</td>
                <td class="center">{{ $resumen->sum('inactivas') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Detalle de constancias</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>Empleado</th><th>Tipo</th><th class="center">Cant.</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>
        </thead>
        <tbody>
            @foreach ($detalle as $c)
                <tr>
                    <td>{{ $c->id_constancias }}</td>
                    <td>{{ $c->nombre_empleado }}</td>
                    <td>{{ $c->tipo_de_constancia }}</td>
                    <td class="center">{{ $c->cantidad }}</td>
                    <td>{{ $c->fecha_de_solicitud->format('d/m/Y') }}</td>
                    <td>{{ $c->hora_de_solicitud }}</td>
                    <td>{{ $c->estado_solicitud ? 'Activa' : 'Inactiva' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
