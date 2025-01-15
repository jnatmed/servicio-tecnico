<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAgenteTable extends AbstractMigration
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
        $table = $this->table('agente');
        $table->addColumn('credencial', 'string')
              ->addColumn('nombre', 'string')
              ->addColumn('apellido', 'string')
              ->addColumn('cuil', 'string', ['limit' => 11, 'signed' => false])
              ->addColumn('dependencia', 'integer', ['signed' => false])
              ->addColumn('estado_agente', 'enum', ['values' => ['activo', 'retirado']])
              ->addIndex(['cuil'], ['unique' => true, 'name' => 'idx_cuil'])  // Definimos un índice único para 'cuil'
              ->addForeignKey('dependencia', 'dependencia', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->create();
    }
}
