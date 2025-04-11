<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CrearTablaResultadoDescuento extends AbstractMigration
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
        $table = $this->table('solicitud_descuento_haberes');

        $table->addColumn('cuota_id', 'integer', ['null' => false, 'signed' => false])
              ->addColumn('fecha_solicitud', 'date', ['null' => true])
              ->addColumn('fecha_resultado', 'date', ['null' => true])
              ->addColumn('resultado', 'enum', [
                  'values' => ['pendiente', 'aprobado', 'rechazado'],
                  'default' => 'pendiente'
              ])
              ->addColumn('motivo', 'text', ['null' => true])
              ->addForeignKey('cuota_id', 'cuota', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
