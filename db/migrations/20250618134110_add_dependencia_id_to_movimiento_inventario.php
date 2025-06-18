<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDependenciaIdToMovimientoInventario extends AbstractMigration
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

        // Agregar columna dependencia_id después de producto_id
        $table->addColumn('dependencia_id', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'producto_id'
            ])
            // Crear clave foránea hacia la tabla dependencia
            ->addForeignKey('dependencia_id', 'dependencia', 'id', [
                'delete' => 'SET NULL',
                'update' => 'NO_ACTION'
            ])
            ->update();
    }
}
