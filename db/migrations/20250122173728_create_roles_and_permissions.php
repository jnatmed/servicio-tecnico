<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolesAndPermissions extends AbstractMigration
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
        // Crear la tabla roles
        // Crear la tabla roles
        $roles = $this->table('roles', ['id' => false, 'primary_key' => ['id']]);
        $roles->addColumn('id', 'integer', ['identity' => true]) // Clave primaria auto incremental
              ->addColumn('nombre', 'string', ['limit' => 50])
              ->addIndex(['nombre'], ['unique' => true, 'name' => 'idx_unique_nombre'])
              ->create();

        // Crear la tabla modulos
        $modulos = $this->table('modulos', ['id' => false, 'primary_key' => ['id']]);
        $modulos->addColumn('id', 'integer', ['signed' => false, 'identity' => true])
                ->addColumn('nombre', 'string', ['limit' => 255])
                ->addColumn('descripcion', 'text', ['null' => true])
                ->create();

        // Crear la tabla permisos
        $permisos = $this->table('permisos', ['id' => false, 'primary_key' => ['id']]);
        $permisos->addColumn('id', 'integer', ['identity' => true]) // Clave primaria auto incremental
                 ->addColumn('modulo_id', 'integer', ['signed' => false])
                 ->addColumn('rol_id', 'integer') // Relación con roles
                 ->addColumn('nivel_acceso', 'enum', [
                     'values' => ['lectura', 'escritura', 'administracion'],
                     'default' => 'lectura'
                 ])
                 ->addForeignKey('modulo_id', 'modulos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                 ->addForeignKey('rol_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                 ->create();

        // Crear la tabla roles_permisos
        $rolesPermisos = $this->table('roles_permisos', ['id' => false, 'primary_key' => ['id']]);
        $rolesPermisos->addColumn('id', 'integer', ['identity' => true]) // Clave primaria auto incremental
                      ->addColumn('rol_id', 'integer')
                      ->addColumn('permiso_id', 'integer')
                      ->addForeignKey('rol_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                      ->addForeignKey('permiso_id', 'permisos', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
                      ->create();

        // Agregar columna rol_id a la tabla usuarios
        $usuarios = $this->table('usuarios');
        $usuarios->addColumn('rol_id', 'integer', ['null' => true])
                 ->addForeignKey('rol_id', 'roles', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
                 ->update();
    }
}
