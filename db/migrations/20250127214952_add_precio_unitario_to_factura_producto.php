<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPrecioUnitarioToFacturaProducto extends AbstractMigration
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
        $table = $this->table('factura_producto');

        $table->addColumn('precio_unitario', 'decimal', [
            'precision' => 10, // Total de dígitos
            'scale' => 2,      // Dígitos después del punto decimal
            'null' => false,   // No permitir valores nulos
            'default' => 0.00  // Valor por defecto
        ])->update();
    }
}
