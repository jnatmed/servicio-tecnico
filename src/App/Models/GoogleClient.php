<?php

namespace Paw\App\Models;

use Exception;
use Google\Service\Oauth2 as Google_Service_Oauth2;
use Google_Client;

class GoogleClient extends Google_Client

{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $authUrl;
    private $scopes;
    public $accessToken;

    public function __construct()
    {
        global $config, $log;
    
        // Verifica que las configuraciones necesarias estén disponibles
        if (empty($config->get('CLIENT_ID')) ||
            empty($config->get('CLIENT_SECRET')) ||
            empty($config->get('REDIRECT_URI')) ||
            empty($config->get('AUTH_URL'))) {
            throw new Exception("Faltan configuraciones necesarias para Google Client");
        }
    
        // Configuración común para la instancia
        $configArray = [
            'client_id' => $config->get('CLIENT_ID'),
            'client_secret' => $config->get('CLIENT_SECRET'),
            'redirect_uri' => $config->get('REDIRECT_URI'),
            'scopes' => [
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::USERINFO_PROFILE
            ],
            'access_type' => 'offline', // Configuración para tokens de actualización
            'state' => bin2hex(random_bytes(16)), // Genera un valor único para CSRF
        ];
    
        // Llama al constructor de la clase base Google_Client pasando el arreglo de configuración
        parent::__construct($configArray);
    
        // Configura los scopes usando el método setScopes
        $this->setScopes($configArray['scopes']);
    
        // Establece el estado y el tipo de acceso explícitamente
        $this->setAccessType($configArray['access_type']);
        $this->setState($configArray['state']);
    
        $this->clientId = $configArray['client_id'];
        $this->clientSecret = $configArray['client_secret'];
        $this->redirectUri = $configArray['redirect_uri'];

        $log->debug("Google Client Created: ", [
            'clientId' => $configArray['client_id'],
            'clientSecret' => $configArray['client_secret'],
            'redirectUri' => $configArray['redirect_uri'],
            'authUrl' => $config->get('AUTH_URL'),
            'accessType' => $configArray['access_type'],
            'state' => $configArray['state']
        ]);
    }
    
    

    /**
     * Genera la URL para autenticación.
     *
     * @return string
     */
    public function createAuthUrl($scope = null, array $queryParams = [])
    {
        global $log;
    
        // Si no se pasa un scope, usa los configurados en la clase
        $scope = $scope ?: $this->requestedScopes;
    
        $authUrl = parent::createAuthUrl();
    
        $log->debug("Creating Auth URL", [
            'access_type' => $this->config['access_type'] ?? 'online',
            'state' => $this->config['state'] ?? 'not_set',
            'scopes' => $this->requestedScopes,
            'authUrl' => $authUrl,
        ]);
    
        return $authUrl;
    }

    /**
     * Intercambia el código de autorización por un token de acceso.
     *
     * @param string $authorizationCode
     * @return mixed
     */
    public function fetchAccessToken($authorizationCode)
    {
        global $log;

        $postData = [
            'code' => $authorizationCode,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ];

        $log->debug("postdata Entre", ['postdata' => $postData]);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]);

        $log->debug("Configuración previa a solicitud", [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $authorizationCode,
            'ch' => $ch
        ]);

        // Inicializar un buffer de salida
        ob_start();
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $response = curl_exec($ch);
        $verboseOutput = ob_get_contents();
        ob_end_clean();  // Terminar el buffer

        // Guardar la salida verbose en los logs
        $log->debug("Salida cURL Verbose", ['verbose_output' => $verboseOutput]);

        curl_close($ch);

        
        $log->debug("Código HTTP", ['http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE)]);

        $result = json_decode($response, true);

        $log->debug("Respuesta completa cURL", ['response' => $result]);

        if (isset($result['error'])) {
            throw new Exception('Error al obtener el token: ' . $result['error_description']);
        }

        return $result;
    }

    public function setAccessToken($accessToken){

        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function receptionCallbacks($authorizationCode, GoogleClient $googleClient)
    {
        global $log;
    
        try {
            // Paso 1: Obtén el token de acceso
            $accessToken = $this->fetchAccessToken($authorizationCode);
            if (is_array($accessToken)) {
                // Paso 2: Configura el token en el cliente Google
                $googleClient->setAccessToken($accessToken);
            } else {
                throw new Exception("PASO 2: El token de acceso no es válido");
            }
    
            $log->debug("fetchAccessToken Access token", ['access_token' => $accessToken]);
            $log->debug("Estado de Google_Client", ['token' => $googleClient->getAccessToken()]);
    

            // Establecer el certificado de CA
            $caFile = __DIR__ . '/cacert.pem';

            if (file_exists($caFile)) {
            // Paso 3: Configura la certificación SSL en el cliente HTTP
                $httpClient = new \GuzzleHttp\Client([
                    'verify' => $caFile, // Cambia esta ruta al archivo actualizado de certificados
                ]);
                $googleClient->setHttpClient($httpClient);                
                $log->debug("Archivo de certificado de CA encontrado", ['cacert_path' => $caFile]);
            } else {
                $log->error("El archivo cacert.pem no se encuentra en la ruta especificada", ['cacert_path' => $caFile]);
            }
    
            // Crea el servicio OAuth2 (usando $googleClient)
            try {
                $oauth2Service = new Google_Service_Oauth2($googleClient);
            } catch (Exception $e) {
                $log->error("PASO 3: Error al inicializar Google_Service_Oauth2", ['exception' => $e->getMessage()]);
                throw new Exception("Fallo en la creación del servicio OAuth2: " . $e->getMessage());
            }
    
            // Paso 4: Obtén la información del usuario
            try {
                $userInfo = $oauth2Service->userinfo->get();
            } catch (Exception $e) {
                $log->error("PASO 4: Error al obtener información del usuario", ['exception' => $e->getMessage()]);
                throw new Exception("No se pudo recuperar la información del usuario: " . $e->getMessage());
            }
    
            $log->debug("User Info", ['user_info' => $userInfo]);
            return $userInfo;
    
        } catch (Exception $e) {
            $log->error("Error en receptionCallbacks", ['exception' => $e->getMessage()]);
            throw new Exception("Error en el proceso de autenticación: " . $e->getMessage());
        }
    }
    
    

}
