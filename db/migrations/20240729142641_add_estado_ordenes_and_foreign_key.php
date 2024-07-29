<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEstadoOrdenesAndForeignKey extends AbstractMigration
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
        // Crear la tabla estado_ordenes
        $this->table('estado_ordenes')
            ->addColumn('descripcion_estado', 'string', ['limit' => 255, 'null' => false])
            ->create();

        // Agregar la columna estado_orden_id a la tabla ordenes
        $table = $this->table('ordenes');
        $table->addColumn('estado_orden_id', 'integer', ['null' => false, 'signed' => false])
              ->addForeignKey('estado_orden_id', 'estado_ordenes', 'id', ['delete'=> 'RESTRICT', 'update'=> 'CASCADE'])
              ->update();
    }
}
