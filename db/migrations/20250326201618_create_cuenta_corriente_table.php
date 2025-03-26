<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCuentaCorrienteTable extends AbstractMigration
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
            ->addColumn('agente_id', 'integer', ['signed' => false])
            ->addColumn('fecha', 'date')
            ->addColumn('descripcion', 'string', ['limit' => 255])
            ->addColumn('tipo_movimiento', 'enum', ['values' => ['debito', 'credito']])
            ->addColumn('monto', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('saldo', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addForeignKey('agente_id', 'agente', 'id', ['delete'=> 'CASCADE'])
            ->create();
    }
}
