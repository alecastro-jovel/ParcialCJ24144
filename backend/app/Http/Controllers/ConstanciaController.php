<?php

namespace App\Http\Controllers;

use App\Mail\ConstanciaLista;
use App\Models\Constancia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConstanciaController extends Controller
{
    public const TIPOS = [
        'Constancia de trabajo',
        'Constancia de salario',
        'Constancia de antigüedad',
        'Constancia de vacaciones',
        'Constancia de estudios',
    ];

    /** Reglas de validación compartidas por store y update */
    private function reglas(): array
    {
        return [
            'nombre_empleado'    => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s.'-]+$/u"],
            'correo'             => ['required', 'email', 'max:150'],
            'movil'              => ['required', 'digits:8'],
            'tipo_de_constancia' => ['required', 'in:' . implode(',', self::TIPOS)],
            'descripcion'        => ['required', 'string', 'min:5', 'max:255'],
            'cantidad'           => ['required', 'integer', 'between:1,10'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre_empleado.regex' => 'El nombre solo puede contener letras.',
            'movil.digits'          => 'El móvil debe tener exactamente 8 dígitos.',
            'cantidad.between'      => 'La cantidad debe estar entre 1 y 10.',
        ];
    }

    /** GET /api/constancias?estado=activas|inactivas&page=1  -> paginado de 5 en 5 */
    public function index(Request $request)
    {
        $query = Constancia::query()->orderByDesc('id_constancias');

        if ($request->query('estado') === 'activas') {
            $query->where('estado_solicitud', true);
        } elseif ($request->query('estado') === 'inactivas') {
            $query->where('estado_solicitud', false);
        }

        return $query->paginate(5)->withQueryString();
    }

    /** POST /api/constancias  (admin y registrador) */
    public function store(Request $request)
    {
        $data = $request->validate($this->reglas(), $this->mensajes());

        $ahora = Carbon::now('America/El_Salvador');

        // Campos AUTOMÁTICOS: fecha, hora y estado
        $constancia = Constancia::create($data + [
            'fecha_de_solicitud' => $ahora->toDateString(),
            'hora_de_solicitud'  => $ahora->format('H:i:s'),
            'estado_solicitud'   => true,
        ]);

        return response()->json($constancia->fresh(), 201);
    }

    /** GET /api/constancias/{id} */
    public function show(Constancia $constancia)
    {
        return response()->json($constancia);
    }

    /** PUT /api/constancias/{id} */
    public function update(Request $request, Constancia $constancia)
    {
        $reglas = $this->reglas() + ['estado_solicitud' => ['sometimes', 'boolean']];
        $data = $request->validate($reglas, $this->mensajes());

        $constancia->update($data);

        return response()->json($constancia->fresh());
    }

    /** DELETE /api/constancias/{id} */
    public function destroy(Constancia $constancia)
    {
        $constancia->delete();
        return response()->json(['message' => 'Constancia eliminada correctamente.']);
    }

    /**
     * POST /api/constancias/{id}/enviar-correo
     * Marca la constancia como lista (estado = false / inactiva) y avisa al empleado por correo.
     */
    public function enviarCorreo(Constancia $constancia)
    {
        $constancia->update(['estado_solicitud' => false]);

        try {
            Mail::to($constancia->correo)->send(new ConstanciaLista($constancia));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'La constancia se marcó como lista, pero no se pudo enviar el correo: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message'    => "Correo enviado a {$constancia->correo}.",
            'constancia' => $constancia->fresh(),
        ]);
    }
}
