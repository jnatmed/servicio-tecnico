<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddCondicionVentaToCuentaCorriente extends AbstractMigration
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

        $table->addColumn('condicion_venta', 'enum', [
            'values' => ['contado','cta_cte','codigo_608','codigo_689'],
            'null' => true,
            'after' => 'descripcion'
        ])->update();
    }
}
