<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInternosTalleresTables extends AbstractMigration
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
        // Tabla 'internos'
        $this->table('internos', ['signed' => false])
            ->addColumn('nombre', 'string', ['limit' => 100])
            ->addColumn('apellido', 'string', ['limit' => 100])
            ->addColumn('conducta', 'enum', ['values' => ['Buena', 'Regular', 'Mala']])
            ->addColumn('peligrosidad', 'enum', ['values' => ['Baja', 'Media', 'Alta']])
            ->addColumn('habilidades', 'text', ['null' => true])
            ->addColumn('fecha_ingreso', 'date')
            ->addColumn('fecha_egreso', 'date', ['null' => true])
            ->create();

        // Tabla 'talleres'
        $this->table('talleres', ['signed' => false])
            ->addColumn('nombre', 'string', ['limit' => 100])
            ->addColumn('cupo', 'integer', ['signed' => false])
            ->addColumn('descripcion', 'text', ['null' => true])
            ->create();

        // Tabla 'asignaciones'
        $this->table('asignaciones', ['signed' => false])
            ->addColumn('interno_id', 'integer', ['signed' => false])
            ->addColumn('taller_id', 'integer', ['signed' => false])
            ->addColumn('fecha_asignacion', 'date')
            ->addForeignKey('interno_id', 'internos', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('taller_id', 'talleres', 'id', ['delete' => 'CASCADE'])
            ->create();

        // Tabla 'lista_espera'
        $this->table('lista_espera', ['signed' => false])
            ->addColumn('interno_id', 'integer', ['signed' => false])
            ->addColumn('taller_id', 'integer', ['signed' => false])
            ->addColumn('prioridad', 'integer', ['signed' => false])
            ->addColumn('fecha_registro', 'date')
            ->addForeignKey('interno_id', 'internos', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('taller_id', 'talleres', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
