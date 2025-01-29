<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateProductoTable extends AbstractMigration
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
        $table = $this->table('producto', ['signed' => false]);

        $table->addColumn('nro_proyecto_productivo', 'string', ['limit' => 50, 'null' => true])
              ->addColumn('descripcion_proyecto', 'string', ['limit' => 255, 'null' => true])  
              ->addColumn('estado', 'enum', ['values' => ['iniciado', 'a_la_venta'], 'null' => true])
              ->addColumn('id_taller', 'integer', ['signed' => false])
              ->addColumn('id_unidad_q_fabrica', 'integer', ['signed' => false])
              ->addColumn('stock_inicial', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => true])
              ->addColumn('unidad_medida', 'enum', [
                  'values' => ['kg', 'bolsas', 'litros', 'unidades','docena'],
                  'default' => 'kg',
                  'null' => false
              ])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['nro_proyecto_productivo', 'descripcion_proyecto'], ['unique' => true, 'name' => 'idx_nro_proyecto_descripcion'])
              ->addForeignKey('id_taller', 'taller', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('id_unidad_q_fabrica', 'unidad', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
