<?php

namespace Paw\App\Utils;

use Exception;
use Paw\Core\Traits\Loggable;

class Uploader
{
    use Loggable;

    const UPLOADDIRECTORY = '../ordenes/';

    public function guardarOrdenPDF($file) {

        // Verificar que es un archivo PDF
        $this->logger->info("hay archivo");
        $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if ($fileType !== "pdf") {
            $this->logger("Solo se permiten archivos PDF.");
            throw new Exception("Solo se permiten archivos PDF.");
        }

        // Generar un nombre aleatorio único para el archivo PDF
        $randomName = uniqid("orden_") . ".pdf";
        $this->logger->info("randomName: $randomName");

        // Ruta completa del archivo a guardar
        $filePath = self::UPLOADDIRECTORY . $randomName;

        $this->logger->info("filePath: $filePath");

        // Intentar mover el archivo cargado al directorio de minutas
        if (move_uploaded_file($file["tmp_name"], $filePath)) {

            return [
                "exito" => true,
                "pathName" => $randomName // Devuelve el nombre del archivo generado
            ];
            
        } else {
            $this->logger("Hubo un error al subir el archivo.");
            return [
                "exito" => false,
                "details" => "Hubo un error al subir el archivo."
            ];
        }     
    }

    public function obtenerMinuta($documentPath) {
        // Construir la ruta completa del archivo PDF
        $fullPath = self::UPLOADDIRECTORY . $documentPath;

        // Verificar si el archivo existe
        if (!file_exists($fullPath)) {
            return false;
        }

        // Leer el contenido del archivo PDF
        return file_get_contents($fullPath);
    }




}