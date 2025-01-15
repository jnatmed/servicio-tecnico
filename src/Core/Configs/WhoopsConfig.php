<?php

namespace Paw\Core\Configs;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;

class WhoopsConfig
{
    private Run $whoops;

    public function __construct()
    {
        $this->whoops = new Run();
    }

    /**
     * Configura Whoops para manejar errores y filtrar variables sensibles
     *
     * @param array $sensitiveKeys Claves de variables sensibles a ocultar
     */
    public function configure(array $sensitiveKeys = []): void
    {
        $handler = new PrettyPageHandler();

        // Agregar un filtro para variables sensibles
        $handler->addDataTableCallback('Environment Variables', function () use ($sensitiveKeys) {
            $envVars = getenv();
            return $this->sanitizeEnvironmentVariables($envVars, $sensitiveKeys);
        });

        $this->whoops->pushHandler($handler);
        $this->whoops->register();
    }

    /**
     * Filtra las variables sensibles de un array de datos
     *
     * @param array $data Datos originales
     * @param array $sensitiveKeys Claves de variables a ocultar
     * @return array Datos filtrados
     */
    private function sanitizeEnvironmentVariables(array $data, array $sensitiveKeys): array
    {
        foreach ($sensitiveKeys as $key) {
            if (isset($data[$key])) {
                $data[$key] = '[REDACTED]'; // Oculta el valor
            }
        }
        return $data;
    }
}
