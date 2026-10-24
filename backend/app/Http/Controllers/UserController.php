<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Listado y administración de usuarios (solo Administrador).
 */
class UserController extends Controller
{
    private function reglas(?User $user = null): array
    {
        return [
            'name'      => ['required', 'string', 'min:3', 'max:100', "regex:/^[\pL\s.'-]+$/u"],
            'email'     => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'documento' => ['nullable', 'regex:/^\d{8}-\d$/'],
            'role'      => ['required', Rule::in(['admin', 'registrador'])],
        ];
    }

    private function mensajes(): array
    {
        return [
            'name.regex'       => 'El nombre solo puede contener letras.',
            'documento.regex'  => 'El documento debe tener el formato 00000000-0.',
            'email.unique'     => 'Ya existe un usuario con ese correo.',
        ];
    }

    private function claveTemporal(): string
    {
        return 'Tmp' . Str::random(6) . random_int(10, 99) . '*';
    }

    private function fila(User $u): array
    {
        return [
            'id'                  => $u->id,
            'name'                => $u->name,
            'email'               => $u->email,
            'documento'           => $u->documento,
            'role'                => $u->role,
            'estado'              => $u->estado,
            'qr_configurado'      => (bool) $u->google2fa_confirmed_at,
            'debe_cambiar_clave'  => $u->must_change_password,
        ];
    }

    /** GET /api/usuarios?nombre=&role=&estado=habilitado|inhabilitado&page=1 */
    public function index(Request $request)
    {
        $q = User::query()->orderBy('name');

        if ($n = trim((string) $request->query('nombre'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$n}%")->orWhere('email', 'like', "%{$n}%"));
        }
        if (in_array($request->query('role'), ['admin', 'registrador'], true)) {
            $q->where('role', $request->query('role'));
        }
        if ($request->query('estado') === 'habilitado') {
            $q->where('estado', true);
        } elseif ($request->query('estado') === 'inhabilitado') {
            $q->where('estado', false);
        }

        return $q->paginate(5)->withQueryString()->through(fn ($u) => $this->fila($u));
    }

    /** POST /api/usuarios  -> crea el usuario con contraseña TEMPORAL (se muestra una sola vez) */
    public function store(Request $request)
    {
        $data = $request->validate($this->reglas(), $this->mensajes());
        $clave = $this->claveTemporal();

        $user = User::create($data + ['password' => $clave, 'estado' => true, 'must_change_password' => true]);

        return response()->json(['user' => $this->fila($user), 'temporary_password' => $clave], 201);
    }

    /** GET /api/usuarios/{user} */
    public function show(User $user)
    {
        return response()->json($this->fila($user));
    }

    /** PUT /api/usuarios/{user}  (datos + habilitar / inhabilitar) */
    public function update(Request $request, User $user)
    {
        $data = $request->validate(
            $this->reglas($user) + ['estado' => ['sometimes', 'boolean']],
            $this->mensajes(),
        );

        if ($user->id === $request->user()->id && (($data['estado'] ?? true) === false || $data['role'] !== $user->role)) {
            return response()->json(['message' => 'No puedes inhabilitarte ni cambiar tu propio rol.'], 422);
        }

        $user->update($data);

        // Un usuario inhabilitado pierde su sesión de inmediato
        if (array_key_exists('estado', $data) && !$data['estado']) {
            $user->tokens()->delete();
        }

        return response()->json($this->fila($user->fresh()));
    }

    /**
     * POST /api/usuarios/{user}/restablecer
     * Genera una nueva contraseña temporal (sirve como "Recuperar contraseña") y,
     * por si perdió el teléfono, también reinicia el QR del registrador.
     */
    public function restablecer(User $user)
    {
        $clave = $this->claveTemporal();

        $user->forceFill([
            'password'               => $clave,
            'must_change_password'   => true,
            'google2fa_secret'       => null,
            'google2fa_confirmed_at' => null,
        ])->save();
        $user->tokens()->delete();

        return response()->json(['user' => $this->fila($user->fresh()), 'temporary_password' => $clave]);
    }
}
