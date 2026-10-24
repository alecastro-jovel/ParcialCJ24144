<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitud_de_constancias', function (Blueprint $table) {
            $table->id('id_constancias');
            $table->string('nombre_empleado', 100);
            $table->string('correo', 150);
            $table->string('movil', 15);
            $table->string('tipo_de_constancia', 60);
            $table->string('descripcion', 255);
            $table->unsignedInteger('cantidad');
            $table->date('fecha_de_solicitud');   // automática
            $table->time('hora_de_solicitud');    // automática
            $table->boolean('estado_solicitud')->default(true); // automática (true = activa)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_de_constancias');
    }
};
