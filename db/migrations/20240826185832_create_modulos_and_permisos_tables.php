<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateModulosAndPermisosTables extends AbstractMigration
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
        // Tabla modulos
        $modulos = $this->table('modulos', ['id' => false, 'primary_key' => ['id']]);
        $modulos->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
                ->addColumn('nombre', 'string', ['limit' => 255])
                ->addColumn('descripcion', 'text', ['null' => true])
                ->create();

        // Tabla permisos
        $permisos = $this->table('permisos', ['id' => false, 'primary_key' => ['id']]);
        $permisos->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
                 ->addColumn('usuario_id', 'integer', ['signed' => false])
                 ->addColumn('modulo_id', 'integer', ['signed' => false])
                 ->addColumn('nivel_acceso', 'enum', [
                     'values' => ['lectura', 'escritura', 'administracion'],
                     'default' => 'lectura'
                 ])
                 ->addForeignKey('usuario_id', 'usuarios', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->addForeignKey('modulo_id', 'modulos', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->create();
    }
}
