<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LoginTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * POST /api/login/{rol}   (rol = admin | registrador)
     * - Administrador: entra directo con correo + contraseña.
     * - Registrador: después de la contraseña debe confirmar el código del QR (Google Authenticator).
     *   Aquí solo recibe un "ticket" temporal; el token real se entrega en /api/2fa/verify.
     */
    public function login(Request $request, string $rol)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Correo o contraseña incorrectos.'], 422);
        }

        if (!$user->estado) {
            return response()->json(['message' => 'Tu usuario está inhabilitado. Contacta al administrador.'], 403);
        }

        if ($user->role !== $rol) {
            return response()->json(['message' => "Este usuario no es {$rol}. Usa el login correcto."], 403);
        }

        // Registrador: falta el segundo paso (QR). Todavía NO se entrega token.
        if ($user->role === 'registrador') {
            return response()->json([
                'requires_2fa' => true,
                'email'        => $user->email,
                'ticket'       => LoginTicket::issue($user),
                'message'      => 'Ingresa el código de tu aplicación autenticadora.',
            ]);
        }

        return $this->conToken($user);
    }

    /** Cambio de la contraseña (obligatorio cuando es temporal) */
    public function cambiarPassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'La confirmación no coincide con la nueva contraseña.',
            'password.different' => 'La nueva contraseña debe ser distinta a la actual.',
            'password.min'       => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ]);

        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
                'errors'  => ['current_password' => ['La contraseña actual es incorrecta.']],
            ], 422);
        }

        $user->update(['password' => $data['password'], 'must_change_password' => false]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.', 'user' => $user->perfil()]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->perfil());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /** Crea el token definitivo (una sola sesión activa por usuario) */
    public static function conToken(User $user)
    {
        $user->tokens()->delete();

        return response()->json([
            'token' => $user->createToken('api-token')->plainTextToken,
            'user'  => $user->perfil(),
        ]);
    }
}
