<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCuotaTable extends AbstractMigration
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
        $table = $this->table('cuota');
        $table->addColumn('factura_id', 'integer', ['signed' => false])  // Foreign key to factura
              ->addColumn('nro_cuota', 'integer', ['signed' => false])
              ->addColumn('estado', 'enum', ['values' => ['pagada', 'pendiente']])
              ->addColumn('fecha_vencimiento', 'date')
              ->addIndex(['factura_id', 'nro_cuota'], ['unique' => true, 'name' => 'idx_factura_nro_cuota'])  // Definimos índice único compuesto
              ->addForeignKey('factura_id', 'factura', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
