<?php

namespace Database\Seeders;

use App\Models\Constancia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuarios de prueba (la contraseña se encripta sola por el cast 'hashed')
        User::create(['name' => 'Administrador', 'email' => 'admin@parcial.com', 'password' => 'Admin123*', 'role' => 'admin', 'documento' => '00000001-1']);
        User::create(['name' => 'Registrador', 'email' => 'registrador@parcial.com', 'password' => 'Registrador123*', 'role' => 'registrador', 'documento' => '00000002-2']);

        // Datos de ejemplo repartidos en varios meses
        $tipos = [
            'Constancia de trabajo', 'Constancia de salario', 'Constancia de antigüedad',
            'Constancia de vacaciones', 'Constancia de estudios',
        ];
        $nombres = [
            'Carlos Martínez', 'Ana López', 'José Hernández', 'María Gómez', 'Luis Ramírez',
            'Sofía Castillo', 'Pedro Flores', 'Lucía Mejía', 'Miguel Ortiz', 'Daniela Rivas',
            'Jorge Alvarado', 'Karla Vásquez', 'Ricardo Molina', 'Paola Cruz',
        ];

        foreach ($nombres as $i => $nombre) {
            $fecha = Carbon::create(2026, 7, 5)->addDays($i * 8);
            Constancia::create([
                'nombre_empleado'    => $nombre,
                'correo'             => 'empleado' . ($i + 1) . '@ejemplo.com',
                'movil'              => '7' . str_pad((string) (1000000 + $i * 7919), 7, '0', STR_PAD_LEFT),
                'tipo_de_constancia' => $tipos[$i % count($tipos)],
                'descripcion'        => 'Solicitud de ejemplo número ' . ($i + 1),
                'cantidad'           => ($i % 3) + 1,
                'fecha_de_solicitud' => $fecha->toDateString(),
                'hora_de_solicitud'  => sprintf('%02d:%02d:00', 8 + ($i % 9), ($i * 7) % 60),
                'estado_solicitud'   => $i % 3 !== 0, // algunas inactivas (ya listas)
            ]);
        }
    }
}
