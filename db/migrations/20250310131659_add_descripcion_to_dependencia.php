<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDescripcionToDependencia extends AbstractMigration
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
        $table = $this->table('dependencia');
        $table->addColumn('descripcion', 'text', [
            'null' => true, // Permitir valores nulos
            'collation' => 'utf8mb4_unicode_ci'
        ])
        ->update();
    }

    public function up(): void
    {
        // Insertar solo las siglas con asociaciones seguras
        $this->execute("
            INSERT INTO dependencia (nombre_dependencia, descripcion) VALUES
            ('13', 'Unidad 13 - Santa Rosa, La Pampa'),
            ('21', 'Unidad 21 - Ciudad Autónoma de Buenos Aires'),
            ('C3', 'Complejo Penitenciario Federal III - General Güemes, Salta'),
            ('C5', 'Complejo Penitenciario Federal V - Senillosa, Neuquén'),
            ('C2', 'Complejo Penitenciario Federal II - Marcos Paz, Buenos Aires'),
            ('7', 'Unidad 7 - Resistencia, Chaco'),
            ('C1', 'Complejo Penitenciario Federal I - Ezeiza, Buenos Aires'),
            ('6', 'Unidad 6 - Rawson, Chubut'),
            ('C4', 'Complejo Penitenciario Federal IV - Ezeiza, Buenos Aires'),
            ('10', 'Unidad 10 - Formosa, Formosa'),
            ('C6', 'Complejo Penitenciario Federal VI - Luján de Cuyo, Mendoza'),
            ('4', 'Unidad 4 - Santa Rosa, La Pampa'),
            ('19', 'Unidad 19 - Ezeiza, Buenos Aires'),
            ('35', 'Unidad 35 - San Martín, Santiago del Estero'),
            ('15', 'Unidad 15 - Río Gallegos, Santa Cruz'),
            ('22', 'Unidad 22 - San Salvador de Jujuy, Jujuy'),
            ('11', 'Unidad 11 - Roque Sáenz Peña, Chaco'),
            ('17', 'Unidad 17 - Candelaria, Misiones'),
            ('34', 'Unidad 34 - Campo de Mayo, Buenos Aires'),
            ('C7', 'Complejo Penitenciario Federal VII - Ezeiza, Buenos Aires'),
            ('12', 'Unidad 12 - Viedma, Río Negro'),
            ('14', 'Unidad 14 - Esquel, Chubut'),
            ('8', 'Unidad 8 - San Salvador de Jujuy, Jujuy'),
            ('5', 'Unidad 5 - General Roca, Río Negro'),
            ('30', 'Unidad 30 - Santa Rosa, La Pampa'),
            ('25', 'Unidad 25 - General Pico, La Pampa')
        ");
    }

}
