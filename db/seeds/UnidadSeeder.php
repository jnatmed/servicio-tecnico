<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class UnidadSeeder extends AbstractSeed
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
            ['nombre' => 'U.19'],
            ['nombre' => 'C.F.J.A.'],
            ['nombre' => 'C.P.F. VI'],
            ['nombre' => 'C.P.F. VII'],
            ['nombre' => 'U.8'],
            ['nombre' => 'C.P.F II'],
            ['nombre' => 'C.P.F. I'],
            ['nombre' => 'C.P.F. IV'],
            ['nombre' => 'U.30'],
        ];

        $this->table('unidad')->insert($data)->save();
    }
}
