<?php

namespace App\Http\Controllers;

use App\Support\LoginTicket;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

/**
 * Login con QR (TOTP / Google Authenticator) - SOLO para el registrador.
 */
class TwoFactorController extends Controller
{
    /**
     * GET /api/2fa/generate
     * Entrega el QR SOLO la primera vez. Cuando ya fue confirmado no se vuelve a mostrar
     * (si no, quien tenga la contraseña podría sacar la clave y saltarse el segundo paso).
     */
    public function generate(Request $request)
    {
        $user = LoginTicket::user($request);

        if ($user->google2fa_confirmed_at) {
            return response()->json(['already_configured' => true]);
        }

        $google2fa = new Google2FA();

        if (!$user->google2fa_secret) {
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        return response()->json([
            'already_configured' => false,
            // El frontend dibuja el QR a partir de esta URL (otpauth://...)
            'otpauth_url'        => $google2fa->getQRCodeUrl('Constancias ULS', $user->email, $user->google2fa_secret),
            'secret'             => $user->google2fa_secret,
        ]);
    }

    /** POST /api/2fa/verify  { code }  -> si es correcto entrega el token definitivo */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6'], [
            'code.required' => 'Escribe los 6 dígitos del código.',
            'code.digits'   => 'El código debe tener 6 dígitos.',
        ]);

        $user = LoginTicket::user($request);

        if (!$user->google2fa_secret) {
            return response()->json(['message' => 'Primero escanea el código QR con tu aplicación.'], 422);
        }

        $valido = (new Google2FA())->verifyKey($user->google2fa_secret, (string) $request->input('code'));

        if (!$valido) {
            return response()->json(['message' => 'Código inválido o expirado.'], 422);
        }

        if (!$user->google2fa_confirmed_at) {
            $user->forceFill(['google2fa_confirmed_at' => now()])->save();
        }

        LoginTicket::forget($request);

        return AuthController::conToken($user);
    }
}
