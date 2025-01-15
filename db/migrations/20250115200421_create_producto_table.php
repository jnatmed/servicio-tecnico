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
        $table = $this->table('producto');
        $table->addColumn('nro_proyecto_productivo', 'string', ['limit' => 50, 'signed' => false]) 
              ->addColumn('fecha_creacion', 'date')
              ->addColumn('descripcion_proyecto', 'text')
              ->addColumn('estado', 'enum', ['values' => ['iniciado', 'para_la_venta']])
              ->addColumn('tipo', 'enum', ['values' => ['producto', 'servicio']])
              ->addColumn('stock_inicial', 'integer', ['signed' => false])
              ->addColumn('unidad_que_fabrica', 'integer', ['signed' => false])
              ->addIndex(['nro_proyecto_productivo'], ['unique' => true, 'name' => 'idx_nro_proyecto_productivo'])  // Definimos el índice único
              ->addForeignKey('unidad_que_fabrica', 'dependencia', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
