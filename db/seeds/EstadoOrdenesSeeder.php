<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class EstadoOrdenesSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        // Datos para insertar
        $data = [
            [
                'id' => 1,
                'descripcion_estado' => 'pendiente_de_aceptacion',
            ],
            [
                'id' => 2,
                'descripcion_estado' => 'aceptado',
            ],
            [
                'id' => 3,
                'descripcion_estado' => 'rechazado',
            ],
            [
                'id' => 4,
                'descripcion_estado' => 'finalizado',
            ],
        ];

        // Insertar los datos en la tabla estado_ordenes
        $this->table('estado_ordenes')->insert($data)->saveData();
    }
}
