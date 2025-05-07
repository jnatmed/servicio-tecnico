<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTableNumeradorFactura extends AbstractMigration
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
        $this->table('numerador_factura')
            ->addColumn('dependencia_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('desde', 'integer', ['null' => false])
            ->addColumn('hasta', 'integer', ['null' => false])
            ->addColumn('ultimo_utilizado', 'integer', ['null' => true, 'default' => null])
            ->addForeignKey('dependencia_id', 'dependencia', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
