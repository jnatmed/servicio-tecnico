<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMovimientoInventarioTable extends AbstractMigration
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
        $table = $this->table('movimiento_inventario');
        $table->addColumn('factura_id', 'integer', ['signed' => false])  // Foreign key to factura
              ->addColumn('producto_id', 'integer', ['signed' => false])  // Foreign key to producto
              ->addColumn('fecha_movimiento', 'datetime')
              ->addColumn('tipo_movimiento', 'enum', ['values' => ['in', 'out']])
              ->addColumn('cantidad', 'integer', ['signed' => false])
              ->addForeignKey('factura_id', 'factura', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('producto_id', 'producto', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
