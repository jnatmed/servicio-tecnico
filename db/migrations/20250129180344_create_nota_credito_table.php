<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNotaCreditoTable extends AbstractMigration
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
        $table = $this->table('nota_credito');
        $table->addColumn('nro_nota_credito', 'string', ['limit' => 50, 'signed' => false])
              ->addColumn('id_factura', 'integer', ['signed' => false])
              ->addColumn('fecha_emision', 'date')
              ->addColumn('motivo', 'text')
              ->addColumn('monto_total', 'decimal', ['precision' => 10, 'scale' => 2])
              ->addColumn('estado', 'enum', [
                  'values' => ['emitida', 'aplicada', 'cancelada'],
                  'default' => 'emitida'
              ])
              ->addIndex(['nro_nota_credito'], ['unique' => true, 'name' => 'idx_nro_nota_credito']) // Índice único
              ->addForeignKey('id_factura', 'factura', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION']) // Relación con factura
              ->create();
    }
}
