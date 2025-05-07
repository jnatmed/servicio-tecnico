<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEstadoSolicitudNumeracionToNumeradorFactura extends AbstractMigration
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
        $table = $this->table('numerador_factura');

        $table
            ->addColumn('expte_pedido_numeracion', 'string', [
                'limit' => 100,
                'null' => true,
                'after' => 'dependencia_id'
            ])
            ->addColumn('fecha_solicitud', 'datetime', [
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'hasta'
            ])
            ->addColumn('estado_solicitud_numeracion', 'enum', [
                'values' => ['pendiente', 'aceptada', 'rechazada'],
                'default' => 'pendiente',
                'after' => 'fecha_solicitud'
            ])
            ->addColumn('motivo_rechazo', 'text', [
                'null' => true,
                'after' => 'estado_solicitud_numeracion'
            ])
            ->update();
    }
}
