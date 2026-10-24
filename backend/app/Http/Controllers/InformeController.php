<?php

namespace App\Http\Controllers;

use App\Models\Constancia;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InformeController extends Controller
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    /** Cantidad de constancias registradas agrupadas por mes */
    private function resumenMensual(): Collection
    {
        return Constancia::orderBy('fecha_de_solicitud')->get()
            ->groupBy(fn ($c) => $c->fecha_de_solicitud->format('Y-m'))
            ->map(function ($items, $clave) {
                [$anio, $mes] = explode('-', $clave);
                return [
                    'mes'       => self::MESES[(int) $mes] . ' ' . $anio,
                    'total'     => $items->count(),
                    'activas'   => $items->where('estado_solicitud', true)->count(),
                    'inactivas' => $items->where('estado_solicitud', false)->count(),
                ];
            })
            ->values();
    }

    /** GET /api/informes/mensual/pdf */
    public function pdf(Request $request)
    {
        $pdf = Pdf::loadView('pdf.informe_mensual', [
            'resumen'  => $this->resumenMensual(),
            'detalle'  => Constancia::orderBy('fecha_de_solicitud')->orderBy('hora_de_solicitud')->get(),
            'generado' => Carbon::now('America/El_Salvador')->format('d/m/Y H:i'),
            'usuario'  => $request->user()->name,
        ])->setPaper('a4');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="informe_constancias_mensual.pdf"',
        ]);
    }

    /**
     * GET /api/informes/mensual/csv
     * Formato tipo reporte de Excel: encabezado con los datos del informe, luego el detalle
     * y al final el resumen por mes.
     */
    public function csv(Request $request)
    {
        $resumen  = $this->resumenMensual();
        $detalle  = Constancia::orderBy('fecha_de_solicitud')->orderBy('hora_de_solicitud')->get();
        $ahora    = Carbon::now('America/El_Salvador');
        $usuario  = $request->user()->name;

        $desde = $detalle->first()?->fecha_de_solicitud?->format('d/m/Y') ?? '-';
        $hasta = $detalle->last()?->fecha_de_solicitud?->format('d/m/Y') ?? '-';

        return response()->streamDownload(function () use ($resumen, $detalle, $ahora, $usuario, $desde, $hasta) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel lea bien las tildes
            $fila = fn (array $f) => fputcsv($out, $f, ',', '"', '\\');

            // Encabezado del reporte
            $fila(['institución', 'ULS']);
            $fila(['sistema', 'Sistema de Gestión de Constancias']);
            $fila(['rango de fecha', "{$desde} - {$hasta}"]);
            $fila(['usuario genera', $usuario]);
            $fila(['fecha hora genera', $ahora->format('d/m/Y H:i')]);
            $fila(['constancias reportadas', $detalle->count()]);
            $fila(['constancias activas', $detalle->where('estado_solicitud', true)->count()]);
            $fila(['constancias inactivas', $detalle->where('estado_solicitud', false)->count()]);
            $fila([]);

            // Detalle
            $fila(['fecha', 'empleado', 'tipo de constancia', 'cantidad', 'estado']);
            foreach ($detalle as $c) {
                $fila([
                    $c->fecha_de_solicitud->format('d/m/Y'),
                    $c->nombre_empleado,
                    $c->tipo_de_constancia,
                    $c->cantidad,
                    $c->estado_solicitud ? 'Activa' : 'Inactiva',
                ]);
            }
            $fila([]);

            // Resumen por mes
            $fila(['mes', 'total registradas', 'activas', 'inactivas']);
            foreach ($resumen as $r) {
                $fila([$r['mes'], $r['total'], $r['activas'], $r['inactivas']]);
            }
            $fila(['TOTAL', $resumen->sum('total'), $resumen->sum('activas'), $resumen->sum('inactivas')]);
            fclose($out);
        }, 'informe_constancias_mensual.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** GET /api/informes/grafico?anio=2026&mes=10  -> datos para el gráfico */
    public function grafico(Request $request)
    {
        $data = $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'mes'  => 'required|integer|between:1,12',
        ]);

        $base = Constancia::whereYear('fecha_de_solicitud', $data['anio'])
            ->whereMonth('fecha_de_solicitud', $data['mes']);

        $activas   = (clone $base)->where('estado_solicitud', true)->count();
        $inactivas = (clone $base)->where('estado_solicitud', false)->count();

        return response()->json([
            'anio'       => (int) $data['anio'],
            'mes'        => (int) $data['mes'],
            'nombre_mes' => self::MESES[(int) $data['mes']],
            'activas'    => $activas,
            'inactivas'  => $inactivas,
            'total'      => $activas + $inactivas,
        ]);
    }
}
