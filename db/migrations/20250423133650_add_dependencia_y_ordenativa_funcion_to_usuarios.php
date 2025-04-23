<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDependenciaYOrdenativaFuncionToUsuarios extends AbstractMigration
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
        $table = $this->table('usuarios');

        $table->addColumn('dependencia_id', 'integer', ['null' => true, 'after' => 'rol_id', 'signed' => false])
              ->addForeignKey('dependencia_id', 'dependencia', 'id', [
                  'delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'
              ])
              ->addColumn('ordenativa_funcion', 'string', ['limit' => 100, 'null' => true, 'after' => 'dependencia_id'])
              ->update();
    }
}
