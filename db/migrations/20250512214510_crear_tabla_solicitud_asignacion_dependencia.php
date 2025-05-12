<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CrearTablaSolicitudAsignacionDependencia extends AbstractMigration
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
        $table = $this->table('solicitud_asignacion_dependencia');
        
        $table->addColumn('usuario_id', 'integer', ['signed' => false])
              ->addColumn('dependencia_id', 'integer', ['signed' => false])
              ->addColumn('estado', 'enum', [
                  'values' => ['solicitado', 'confirmado', 'rechazado'],
                  'default' => 'solicitado'
              ])
              ->addColumn('fecha_solicitud', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('observaciones', 'text', ['null' => true])
              ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete'=> 'CASCADE'])
              ->addForeignKey('dependencia_id', 'dependencia', 'id', ['delete'=> 'CASCADE'])
              ->create();
    }
}
