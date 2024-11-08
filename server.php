<?php
// Define el puerto donde quieres que escuche el servidor
$port = 8080;

// Directorio raíz del servidor (carpeta "public")
$documentRoot = __DIR__ . '/public';

// Iniciar el servidor embebido de PHP en el puerto especificado
echo "Iniciando servidor en http://localhost:$port...\n";
exec("php -S localhost:$port -t $documentRoot");