<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuditoriaTable extends AbstractMigration
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
        $table = $this->table('auditoria', ['id' => 'id']);
        $table->addColumn('tabla_afectada', 'string', ['limit' => 50])
              ->addColumn('operacion', 'string', ['limit' => 10])
              ->addColumn('id_registro_afectado', 'integer', ['null' => true])
              ->addColumn('usuario', 'string', ['limit' => 50])
              ->addColumn('fecha', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('datos_previos', 'json', ['null' => true])
              ->addColumn('datos_nuevos', 'json', ['null' => true])
              ->create();
    }
}
