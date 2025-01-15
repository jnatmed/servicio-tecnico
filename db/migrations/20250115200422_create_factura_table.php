<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFacturaTable extends AbstractMigration
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
        $table = $this->table('factura');
        $table->addColumn('nro_factura', 'string', ['limit' => 50, 'signed' => false])
              ->addColumn('fecha_factura', 'date')
              ->addColumn('unidad_que_factura', 'integer', ['signed' => false])
              ->addColumn('total_facturado', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('condicion_venta', 'enum', ['values' => ['contado', 'cta_cte', 'codigo_608', 'codigo_689']])
              ->addColumn('condicion_impositiva', 'enum', ['values' => ['consumidor_final', 'exento', 'no_responsable', 'responsable_monotributo', 'responsable_inscripto']])
              ->addColumn('id_agente', 'integer', ['signed' => false])
              ->addIndex(['nro_factura'], ['unique' => true, 'name' => 'idx_nro_factura'])  // Definir el índice único para nro_factura
              ->addForeignKey('unidad_que_factura', 'dependencia', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addForeignKey('id_agente', 'agente', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
