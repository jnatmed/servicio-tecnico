<?php

/**
 * Require a view.
 *
 * @param  string $name
 * @param  array  $data
 */
function view($name, $data = [])
{
    global $log, $twig;

    try {
        // $log->debug('Datos en la vista', [$name, $data, $twig]);
        echo $twig->render("{$name}.html", $data);
    } catch (\Twig\Error\Error $e) {
        $log->error('Error al renderizar la plantilla', ['exception' => $e]);
        echo 'Error al renderizar la plantilla: ' . $e->getMessage();
    }

}

/**
 * Redirect to a new page.
 *
 * @param  string $path
 */
function redirect($path)
{
    header("Location: /{$path}");
}

/**
 * Invierte un valor booleano.
 *
 * @param mixed $value El valor a invertir.
 * @return bool El valor invertido.
 */
function not($value)
{
    return !$value;
}


/**
 * Extrae datos específicos de un array de entrada y los asigna a nuevas claves.
 *
 * @param array $input El array de entrada del que se extraerán los datos
 * @param array $keysToExtract Las claves que se quieren extraer del array de entrada
 * @param array $newKeys Las nuevas claves que se asignarán a los valores extraídos
 * @return array Un nuevo array con los datos extraídos y las nuevas claves asignadas
 * @throws InvalidArgumentException Si la cantidad de claves a extraer no coincide con la cantidad de nuevas claves
 */
function arrayExtractData(array $input, array $keysToExtract, array $newKeys)
{
    if (count($keysToExtract) !== count($newKeys)) {
        throw new InvalidArgumentException("La cantidad de claves de entrada debe coincidir con la de las nuevas claves.");
    }

    $result = [];
    foreach ($keysToExtract as $index => $key) {
        if (array_key_exists($key, $input)) {
            $result[$newKeys[$index]] = $input[$key];
        }
    }

    return $result;
}
