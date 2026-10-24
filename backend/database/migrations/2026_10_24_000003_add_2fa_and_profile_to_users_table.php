<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Login con QR (Google Authenticator) - solo lo usa el registrador
            $table->text('google2fa_secret')->nullable();
            $table->timestamp('google2fa_confirmed_at')->nullable();

            // Listado de usuarios y contraseña temporal
            $table->string('documento', 10)->nullable();            // 00000000-0
            $table->boolean('estado')->default(true);               // true = Habilitado | false = Inhabilitado
            $table->boolean('must_change_password')->default(false); // true = debe cambiar la contraseña temporal
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google2fa_secret', 'google2fa_confirmed_at', 'documento', 'estado', 'must_change_password']);
        });
    }
};
