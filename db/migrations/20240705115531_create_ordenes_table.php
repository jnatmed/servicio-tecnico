<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrdenesTable extends AbstractMigration
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
        // Crear la tabla 'ordenes'
        $table = $this->table('ordenes');
        
        // Definir las columnas de la tabla
        $table->addColumn('tipoServicio', 'string', ['limit' => 255])
              ->addColumn('fechaEmision', 'datetime')
              ->addColumn('apellido', 'string', ['limit' => 255])
              ->addColumn('nombre', 'string', ['limit' => 255])
              ->addColumn('grado', 'string', ['limit' => 255])
              ->addColumn('credencial', 'string', ['limit' => 255])
              ->addColumn('division', 'string', ['limit' => 255])
              ->addColumn('seccion', 'string', ['limit' => 255])
              ->addColumn('email', 'string', ['limit' => 255])
              ->addColumn('observaciones', 'text', ['null' => true])
              ->addColumn('pathOrden', 'string', ['limit' => 255, 'null' => true]) 
              ->addTimestamps() 
              ->create();
              
        // Crear la tabla 'usuarios'
        $table = $this->table('usuarios');
        
        // Definir las columnas de la tabla
        $table->addColumn('usuario', 'string', ['limit' => 50])
              ->addColumn('contrasenia', 'string', ['limit' => 255])
              ->addColumn('tipo_usuario', 'enum', ['values' => ['tecnico', 'administrativo']])
              ->addColumn('email', 'string', ['limit' => 100])
              ->addTimestamps()  // Añade las columnas created_at y updated_at
              ->create();           
    }
}
