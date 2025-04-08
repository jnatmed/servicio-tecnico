<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddMontosParcialesToCuota extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $this->table('cuota')
            ->addColumn('monto_pagado', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00, 'after' => 'monto'])
            ->addColumn('monto_reprogramado', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0.00, 'after' => 'monto_pagado'])
            ->update();
    }
}
