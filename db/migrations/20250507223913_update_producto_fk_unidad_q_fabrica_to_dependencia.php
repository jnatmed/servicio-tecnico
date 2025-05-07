<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateProductoFkUnidadQFabricaToDependencia extends AbstractMigration
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

        // Eliminar la FK anterior (asegúrate que este nombre coincida con tu DB)
        $table->dropForeignKey('id_unidad_q_fabrica');

        // Agregar nueva FK hacia dependencia(id)
        $table->addForeignKey('id_unidad_q_fabrica', 'dependencia', 'id', [
            'delete'=> 'CASCADE',
            'update'=> 'NO_ACTION',
        ])->update();
    }
}
