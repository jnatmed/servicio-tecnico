<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCamposExtraToAgente extends AbstractMigration
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

        $table
            ->addColumn('beneficio', 'string', ['null' => true, 'limit' => 100])
            ->addColumn('grado', 'string', ['null' => true, 'limit' => 100])
            ->addColumn('caracter', 'string', ['null' => true, 'limit' => 100])
            ->addColumn('observaciones_supervivencia', 'string', ['null' => true, 'limit' => 255])
            ->addColumn('domicilio', 'string', ['null' => true, 'limit' => 255])
            ->addColumn('telefono', 'string', ['null' => true, 'limit' => 100])
            ->addColumn('mail', 'string', ['null' => true, 'limit' => 150])
            ->update();
    }
}
