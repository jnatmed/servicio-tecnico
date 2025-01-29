<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class TallerSeeder extends AbstractSeed
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
        $data = [
            ['nombre' => 'QUESERIA'],
            ['nombre' => 'ELABORACION DE ALIMENTO BALANCEADO'],
            ['nombre' => 'PANADERIA'],
            ['nombre' => 'ARTESANIAS REGIONALES'],
            ['nombre' => 'CARPINTERIA'],
            ['nombre' => 'SASTRERIA'],
            ['nombre' => 'CARPINTERIA/HERRERIA'],
            ['nombre' => 'CARPINTERIA DE ALUMINIO'],
            ['nombre' => 'SASTRERIA/COSTURA'],
            ['nombre' => 'FIBROFACIL'],
            ['nombre' => 'ENCUADERNACION'],
            ['nombre' => 'REPOSTERIA'],
            ['nombre' => 'LAVADERO AUTOMOTOR'],
            ['nombre' => 'HERRERIA'],
            ['nombre' => 'COSTURA/SASTRERIA'],
        ];

        $this->table('taller')->insert($data)->save();
    }
}
