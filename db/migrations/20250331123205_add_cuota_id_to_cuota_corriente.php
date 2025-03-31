<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCuotaIdToCuotaCorriente extends AbstractMigration
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
        $table = $this->table('cuenta_corriente');

        $table
            ->addColumn('cuota_id', 'integer', ['null' => true, 'signed' => false])
            ->addForeignKey('cuota_id', 'cuota', 'id', [
                'delete'=> 'SET_NULL',
                'update'=> 'NO_ACTION'
            ])
            ->update();
    }
}
