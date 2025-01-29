<?php 

$configFacturacion = [
    'dependencias' => [
        ['id' => 'CPFCABA', 'nombre' => 'Complejo Penitenciario de la Ciudad Autónoma de Buenos Aires'],
        ['id' => 'CFJA', 'nombre' => 'Complejo Penitenciario de Jóvenes Adultos'],
        ['id' => 'CPF1', 'nombre' => 'Complejo Penitenciario Federal I de Ezeiza'],
        // Añadir más dependencias aquí si es necesario
    ],
    'condicion_venta' => ['contado', 'efectivo', 'codigo608', 'codigo689'],
    'condicion_impositiva' => ['Responsable Inscripto', 'Monotributista']
];