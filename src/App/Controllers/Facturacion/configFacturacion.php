<?php 

$configFacturacion = [
    'dependencias' => [
        ['id' => 'CPFCABA', 'nombre' => 'Complejo Penitenciario de la Ciudad Autónoma de Buenos Aires'],
        ['id' => 'CFJA', 'nombre' => 'Complejo Penitenciario de Jóvenes Adultos'],
        ['id' => 'CPF1', 'nombre' => 'Complejo Penitenciario Federal I de Ezeiza'],
        // Añadir más dependencias aquí si es necesario
    ],
    'condicion_venta' => ['contado','cta_cte','codigo_608','codigo_689'],
    'condicion_impositiva' => ['consumidor_final','exento','no_responsable','responsable_monotributo','responsable_inscripto']
];