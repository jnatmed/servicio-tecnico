<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTableTrasladoStock extends AbstractMigration
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
        $table = $this->table('traslado_stock');

        $table
            ->addColumn('producto_id', 'integer', ['signed' => false])
            ->addColumn('cantidad', 'integer')

            ->addColumn('dependencia_origen_id', 'integer', ['signed' => false])
            ->addColumn('dependencia_destino_id', 'integer', ['signed' => false])

            ->addColumn('numero_remito', 'string', ['limit' => 50])
            ->addColumn('estado', 'enum', [
                'values' => ['pendiente', 'recibido', 'cancelado'],
                'default' => 'pendiente'
            ])

            ->addColumn('fecha_despacho', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('fecha_vencimiento', 'datetime')
            ->addColumn('fecha_confirmacion', 'datetime', ['null' => true])

            ->addColumn('comprobante_ingreso_path', 'string', ['limit' => 255, 'null' => true])

            ->addColumn('usuario_origen_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('usuario_destino_id', 'integer', ['null' => true, 'signed' => false])

            ->addColumn('motivo_cancelacion', 'text', ['null' => true])

            ->addTimestamps() // created_at y updated_at

            ->addForeignKey('producto_id', 'producto', 'id')
            ->addForeignKey('dependencia_origen_id', 'dependencia', 'id')
            ->addForeignKey('dependencia_destino_id', 'dependencia', 'id')
            ->addForeignKey('usuario_origen_id', 'usuarios', 'id', ['delete'=> 'SET_NULL'])
            ->addForeignKey('usuario_destino_id', 'usuarios', 'id', ['delete'=> 'SET_NULL'])

            ->create();
    }
}
