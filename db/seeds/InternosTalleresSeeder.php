<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class InternosTalleresSeeder extends AbstractSeed
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
        // Insertar datos en 'internos'
        $internos = [
            ['nombre' => 'Juan', 'apellido' => 'Pérez', 'conducta' => 'Buena', 'peligrosidad' => 'Baja', 'habilidades' => 'Carpintería', 'fecha_ingreso' => '2023-01-15'],
            ['nombre' => 'María', 'apellido' => 'Gómez', 'conducta' => 'Regular', 'peligrosidad' => 'Media', 'habilidades' => 'Costura', 'fecha_ingreso' => '2023-02-10'],
            ['nombre' => 'Pedro', 'apellido' => 'López', 'conducta' => 'Buena', 'peligrosidad' => 'Alta', 'habilidades' => 'Electricidad', 'fecha_ingreso' => '2023-03-05'],
        ];
        $this->table('internos')->insert($internos)->saveData();

        // Insertar datos en 'talleres'
        $talleres = [
            ['nombre' => 'Carpintería', 'cupo' => 10, 'descripcion' => 'Taller de carpintería básica.'],
            ['nombre' => 'Costura', 'cupo' => 8, 'descripcion' => 'Taller de costura y diseño textil.'],
            ['nombre' => 'Electricidad', 'cupo' => 5, 'descripcion' => 'Taller de instalaciones eléctricas.'],
        ];
        $this->table('talleres')->insert($talleres)->saveData();

        // Insertar datos en 'lista_espera'
        $listaEspera = [
            ['interno_id' => 1, 'taller_id' => 1, 'prioridad' => 1, 'fecha_registro' => '2023-12-01'],
            ['interno_id' => 2, 'taller_id' => 2, 'prioridad' => 2, 'fecha_registro' => '2023-12-02'],
            ['interno_id' => 3, 'taller_id' => 3, 'prioridad' => 3, 'fecha_registro' => '2023-12-03'],
        ];
        $this->table('lista_espera')->insert($listaEspera)->saveData();

        // Insertar datos en 'asignaciones'
        $asignaciones = [
            ['interno_id' => 1, 'taller_id' => 1, 'fecha_asignacion' => '2023-12-15'],
            ['interno_id' => 2, 'taller_id' => 2, 'fecha_asignacion' => '2023-12-16'],
            ['interno_id' => 3, 'taller_id' => 3, 'fecha_asignacion' => '2023-12-17'],
        ];
        $this->table('asignaciones')->insert($asignaciones)->saveData();
    }
}
