<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class ProductoSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = [
            ['nro_proyecto_productivo' => 'EX-2024-129132469- -APN-U19#SPF', 'descripcion_proyecto' => 'QUESO TIPO CUARTIROLO', 'estado' => 'a_la_venta', 'id_taller' => 1, 'id_unidad_q_fabrica' => 1, 'stock_inicial' => 1000, 'unidad_medida' => 'kg'],
            ['nro_proyecto_productivo' => 'EX-2024-140627920-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'INFO COSTO SERVICIO DE 2 REPRODUCTOR', 'estado' => 'a_la_venta', 'id_taller' => 2, 'id_unidad_q_fabrica' => 2, 'stock_inicial' => 25.586, 'unidad_medida' => 'kg'],
            ['nro_proyecto_productivo' => 'EX-2024-140627920-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'INFO COSTO SERVICIO DE 2 LECHON', 'estado' => 'a_la_venta', 'id_taller' => 2, 'id_unidad_q_fabrica' => 2, 'stock_inicial' => 4900, 'unidad_medida' => 'kg'],
            ['nro_proyecto_productivo' => 'EX-2024-128123447- -APN-CPF6#SPF', 'descripcion_proyecto' => 'BUDIN DE NARANJA', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 3, 'stock_inicial' => 100, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-140514716-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'PASTAFROLA DE BATA Y MEMBRILLO', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 4, 'stock_inicial' => 12, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-140514716-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'EMPANADA DE CARNE POR DOCENA', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 4, 'stock_inicial' => 12, 'unidad_medida' => 'docena'],
            ['nro_proyecto_productivo' => 'EX-2024-140514716-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'EMPANADA DE POLLO POR DOCENA', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 4, 'stock_inicial' => 13, 'unidad_medida' => 'docena'],
            ['nro_proyecto_productivo' => 'EX-2024-140514716-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'ALFAJORES DE MAICENA POR DOCENA', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 4, 'stock_inicial' => 12, 'unidad_medida' => 'docena'],
            ['nro_proyecto_productivo' => 'EX-2024-140514716-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'PIZZETAS COMPLETAS POR DOCENA', 'estado' => 'a_la_venta', 'id_taller' => 3, 'id_unidad_q_fabrica' => 4, 'stock_inicial' => 25, 'unidad_medida' => 'docena'],
            ['nro_proyecto_productivo' => 'EX-2024-129334487-   -APN-U8#SPF', 'descripcion_proyecto' => 'MACETAS PINTADAS A MANO', 'estado' => 'a_la_venta', 'id_taller' => 4, 'id_unidad_q_fabrica' => 5, 'stock_inicial' => 10, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-122826439- -APN-CPF6#SPF', 'descripcion_proyecto' => 'Mesa plegable de pino (0,75x0, 80x0, 65)', 'estado' => 'a_la_venta', 'id_taller' => 5, 'id_unidad_q_fabrica' => 3, 'stock_inicial' => 24, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-131295239- -APN-CPF6#SPF', 'descripcion_proyecto' => 'TOALLA', 'estado' => 'a_la_venta', 'id_taller' => 6, 'id_unidad_q_fabrica' => 3, 'stock_inicial' => 40, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-131295239- -APN-CPF6#SPF', 'descripcion_proyecto' => 'TOALLON', 'estado' => 'a_la_venta', 'id_taller' => 6, 'id_unidad_q_fabrica' => 3, 'stock_inicial' => 40, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2025-03112205-   -APN-CPF2DT#SPF', 'descripcion_proyecto' => 'JUEGO DE PUPITRE BIPERSONAL CON DOS SILLAS', 'estado' => 'a_la_venta', 'id_taller' => 7, 'id_unidad_q_fabrica' => 6, 'stock_inicial' => 150, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-140015865-   -APN-CPF1DT#SPF', 'descripcion_proyecto' => 'VENTANA DE ALUMINIO 0,70 X 0,85 MTS.', 'estado' => 'a_la_venta', 'id_taller' => 8, 'id_unidad_q_fabrica' => 7, 'stock_inicial' => 35, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-140015865-   -APN-CPF1DT#SPF', 'descripcion_proyecto' => 'VENTANA DE ALUMINIO 1,20 X 1,20 MTS.', 'estado' => 'a_la_venta', 'id_taller' => 8, 'id_unidad_q_fabrica' => 7, 'stock_inicial' => 35, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-129334487-   -APN-U8#SPF', 'descripcion_proyecto' => 'MACETAS DE CERAMICA', 'estado' => 'a_la_venta', 'id_taller' => 4, 'id_unidad_q_fabrica' => 5, 'stock_inicial' => 20, 'unidad_medida' => 'unidades'],
            ['nro_proyecto_productivo' => 'EX-2024-140627920-   -APN-DP#ENCOPE', 'descripcion_proyecto' => 'TORTA DE FRUTOS ROJOS', 'estado' => 'a_la_venta', 'id_taller' => 9, 'id_unidad_q_fabrica' => 2, 'stock_inicial' => 50, 'unidad_medida' => 'unidades'],

        ];

        $this->table('producto')->insert($data)->save();
    }
}
