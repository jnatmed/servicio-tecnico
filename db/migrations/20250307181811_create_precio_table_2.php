<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePrecioTable2 extends AbstractMigration
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
        $table = $this->table('precio');
        $table->addColumn('precio', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('pv_autorizacion_consejo', 'string', ['limit' => 50, 'signed' => false])
              ->addColumn('fecha_precio', 'date')
              ->addColumn('id_producto', 'integer', ['signed' => false])  
              ->addIndex(['pv_autorizacion_consejo'], ['unique' => true, 'name' => 'idx_pv_autorizacion_consejo'])  // Definir índice único para pv_autorizacion_consejo
            //   ->addForeignKey('id_producto', 'producto', 'nro_proyecto_productivo', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('id_producto', 'producto', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
