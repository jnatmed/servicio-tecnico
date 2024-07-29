<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddUserForeignKeyToOrders extends AbstractMigration
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
        // Modificar la tabla `ordenes` para agregar la columna `usuario_id`
        $table = $this->table('ordenes');
        
        // Añadir la columna `usuario_id` (int unsigned) y establecer como clave foránea
        $table->addColumn('usuario_id', 'integer', [
            'null' => false,
            'signed' => false,
            'after' => 'estado_orden_id', // Opcional: para posicionar la columna
        ])
        ->addForeignKey('usuario_id', 'usuarios', 'id', [
            'delete'=> 'RESTRICT', 
            'update'=> 'CASCADE'
        ])
        ->update();
    }
}
