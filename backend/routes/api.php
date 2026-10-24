<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConstanciaController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PasswordChangedMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

// ---------- Públicas ----------
Route::post('/login/{rol}', [AuthController::class, 'login'])->whereIn('rol', ['admin', 'registrador'])->middleware('throttle:10,1');

// Segundo paso del REGISTRADOR (login con QR). No usan token: se identifican con el
// header X-Login-Ticket que entrega /login/registrador. Tienen límite de intentos por minuto.
Route::get('/2fa/generate', [TwoFactorController::class, 'generate'])->middleware('throttle:10,1');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->middleware('throttle:5,1');

// ---------- Requieren token (Sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {

    // Permitidas aun con contraseña temporal
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password', [AuthController::class, 'cambiarPassword']);

    // El resto exige haber cambiado la contraseña temporal
    Route::middleware(PasswordChangedMiddleware::class)->group(function () {

        // Admin y Registrador: registrar constancias + informes mensuales (PDF y CSV)
        Route::middleware(RoleMiddleware::class . ':admin,registrador')->group(function () {
            Route::post('/constancias', [ConstanciaController::class, 'store']);
            Route::get('/informes/mensual/pdf', [InformeController::class, 'pdf']);
            Route::get('/informes/mensual/csv', [InformeController::class, 'csv']);
        });

        // Solo Administrador: CRUD completo, correo, gráfico y usuarios
        Route::middleware(RoleMiddleware::class . ':admin')->group(function () {
            Route::get('/constancias', [ConstanciaController::class, 'index']);
            Route::get('/constancias/{constancia}', [ConstanciaController::class, 'show']);
            Route::put('/constancias/{constancia}', [ConstanciaController::class, 'update']);
            Route::delete('/constancias/{constancia}', [ConstanciaController::class, 'destroy']);
            Route::post('/constancias/{constancia}/enviar-correo', [ConstanciaController::class, 'enviarCorreo']);
            Route::get('/informes/grafico', [InformeController::class, 'grafico']);

            Route::get('/usuarios', [UserController::class, 'index']);
            Route::post('/usuarios', [UserController::class, 'store']);
            Route::get('/usuarios/{user}', [UserController::class, 'show']);
            Route::put('/usuarios/{user}', [UserController::class, 'update']);
            Route::post('/usuarios/{user}/restablecer', [UserController::class, 'restablecer']);
        });
    });
});
