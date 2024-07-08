<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UserSeeder extends AbstractSeed
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
        // Definir los datos de los usuarios
        $data = [
            [
                'usuario' => 'tecnico1',
                'contrasenia' => password_hash('password123', PASSWORD_BCRYPT),
                'tipo_usuario' => 'tecnico',
                'email' => 'tecnico1@example.com',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'usuario' => 'administrativo1',
                'contrasenia' => password_hash('password123', PASSWORD_BCRYPT),
                'tipo_usuario' => 'administrativo',
                'email' => 'administrativo1@example.com',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Insertar los datos en la tabla 'usuarios'
        $this->table('usuarios')->insert($data)->saveData();
    }
}
