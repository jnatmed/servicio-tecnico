<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDescripcionAndPathComprobanteToMovimientoInventario extends AbstractMigration
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
        $table
            ->addColumn('descripcion_decomiso', 'text', ['null' => true, 'after' => 'cantidad'])
            ->addColumn('path_comprobante_decomiso', 'string', ['limit' => 255, 'null' => true, 'after' => 'descripcion_decomiso'])
            ->update();
    }
}
