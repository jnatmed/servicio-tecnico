<?php 

namespace Paw\App\Models;

use Paw\Core\Model;
use Exception;
use Paw\Core\Traits\Loggable;
use Paw\Core\Config;

class LDAP extends Model 
{
    use Loggable;
    private $ldap_conn;
    private $log;
    public $ldap_host;
    public $ldap_port;
    public $ldap_user;
    public $ldap_pass;
    public $base_dn;

    public function __construct(Config $config)
    {
        // parent::__construct();
        
        // Cargar las variables de entorno
        $this->loadEnvVariables($config);
        $this->connect();
        $this->authenticateAdmin();
    }

    private function loadEnvVariables(Config $config)
    {
        $this->ldap_host = $config->get('LDAP_HOST');
        $this->ldap_port = $config->get('LDAP_PORT'); // Puerto por defecto
        $this->ldap_user = $config->get('LDAP_ADMIN_USER');
        $this->ldap_pass = $config->get('LDAP_ADMIN_PASSWORD');
        $this->base_dn = $config->get('LDAP_BASE_DN');

        if (!$this->ldap_host || !$this->ldap_user || !$this->ldap_pass || !$this->base_dn) {
            throw new Exception("Faltan variables de entorno LDAP requeridas.");
        }
    }

    public function connect()
    {
        global $log;
        // Conectar al servidor LDAP
        $this->ldap_conn = ldap_connect($this->ldap_host, $this->ldap_port);

        if (!$this->ldap_conn) {
            $log->error("🔐 No se pudo conectar al servidor LDAP: {$this->ldap_host}:{$this->ldap_port}");
            throw new Exception("No se pudo conectar al servidor LDAP.");
        }

        // Configurar opciones LDAP
        ldap_set_option($this->ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3); // Usar LDAPv3
        ldap_set_option($this->ldap_conn, LDAP_OPT_REFERRALS, 0); // Desactivar referencias
        $log->info("🔐 Conexión exitosa al servidor LDAP: {$this->ldap_host}:{$this->ldap_port}");
    }

    public function authenticateAdmin()
    {
        global $log;
        // Intentar hacer el bind con el usuario administrador
        $bind = ldap_bind($this->ldap_conn, $this->ldap_user, $this->ldap_pass);

        if (!$bind) {
            $error = ldap_error($this->ldap_conn);
            $log->error("🔐 No se pudo autenticar al servidor LDAP con el usuario: {$this->ldap_user}. Error: $error");
            throw new Exception("No se pudo autenticar al servidor LDAP.");
        }

        $log->info("🔐 Autenticación exitosa con el usuario: {$this->ldap_user}");
    }

    public function findUserByUsername($username)
    {
        // Se usa un objeto global de registro de errores
        global $log;
    
        // Filtro de búsqueda LDAP para encontrar el usuario por su nombre de cuenta (samAccountName)
        $search_filter = "(samaccountname=$username)";
        
        // Atributos que se desean obtener para el usuario
        $attributes = ["dn", "cn", "samaccountname"];
    
        // Realiza la búsqueda en el servidor LDAP usando el filtro y los atributos solicitados
        $search = ldap_search($this->ldap_conn, $this->base_dn, $search_filter);
    
        // Verifica si la búsqueda LDAP fue exitosa
        if (!$search) {
            // Si la búsqueda falló, obtiene el mensaje de error y lo registra
            $error = ldap_error($this->ldap_conn);
            $log->error("Error al realizar la búsqueda LDAP: $error");
            // Lanza una excepción con un mensaje de error
            throw new Exception("Error al realizar la búsqueda LDAP.");
        }
    
        // Obtiene las entradas de la búsqueda LDAP
        $entries = ldap_get_entries($this->ldap_conn, $search);
    
        // Si se encontraron entradas, se procesa la primera
        if ($entries["count"] > 0) {
            // Obtiene el Distinguished Name (DN) del usuario encontrado
            $user_dn = $entries[0]["dn"];
            
            // Extrae el nombre común (cn) del usuario (por ejemplo, el nombre completo)
            $name = $entries[0]["cn"][0] ?? null; // Si no existe, se asigna null
            
            // Extrae el nombre de cuenta (samaccountname)
            $account = $entries[0]["samaccountname"]["0"]; // Se asegura de obtener el primer valor
            
            // Extrae el correo electrónico del usuario (userPrincipalName)
            $email = $entries[0]["userprincipalname"][0] ?? null; // Si no existe, se asigna null
    
            // Extrae los grupos a los que pertenece el usuario (memberof)
            $memberof = $entries[0]["memberof"] ?? [];
            $group_name = null;
            
            // Recorre los grupos para encontrar el nombre de uno específico (ejemplo: DEPARTAMENTO DE ASISTENCIA TECNICA)
            foreach ($memberof as $key => $value) {
                // Utiliza una expresión regular para obtener el nombre del grupo
                if (is_string($value) && preg_match('/^CN=([^,]+)/', $value, $matches)) {
                    $group_name = $matches[1]; // Captura solo el valor después de "CN="
                    break; // Solo toma el primer grupo encontrado
                }
            }
    
            // Registra la información del usuario encontrado
            $log->info("🔐 Usuario encontrado. DN: $user_dn", $entries[0]);
    
            // Devuelve la información relevante del usuario como un array
            return [
                'name' => $name,
                'account' => $account,
                'group' => $group_name,
                'email' => $email,
                'dn' => $user_dn,
            ];
        } else {
            // Si no se encuentra al usuario, se registra una advertencia
            $log->warning("🔐 No se encontró al usuario '$username' en el directorio LDAP.");
            
            // Retorna null si no se encontró el usuario
            return null;
        }
    }
    

    public function authenticateUser($username, $password)
    {
        global $log;
    
        try {
            // Buscar información del usuario en LDAP
            $user_info = $this->findUserByUsername($username);
    
            // Si el usuario no existe, lanzamos una excepción
            if (!$user_info) {
                throw new Exception("Usuario no encontrado.");
            }
    
            // Intentar hacer el bind con el LDAP para autenticar al usuario
            try {
                $bind = ldap_bind($this->ldap_conn, $user_info['dn'], $password);
    
                // Si la autenticación es exitosa
                if ($bind) {
                    $log->info("Autenticación exitosa para el usuario: " . $user_info['name'], [$user_info]);
                    return $user_info;
                } else {
                    // Si la autenticación falla, obtener el error de LDAP y lanzar una excepción
                    $error = ldap_error($this->ldap_conn);
                    throw new Exception("No se pudo autenticar al usuario '$username'. Error: $error");
                }
            } catch (Exception $e) {
                // Si ocurre un error durante el ldap_bind
                $log->error("Error en el bind de LDAP: " . $e->getMessage());
                return false;
            }
            
        } catch (Exception $e) {
            // Captura errores relacionados con la búsqueda de usuario o autenticación
            $log->error($e->getMessage());
            return false;
        }
    }
    
    

    public function close()
    {
        global $log;
        // Cerrar la conexión LDAP        
        if ($this->ldap_conn) {
            ldap_close($this->ldap_conn);
            $log->info("Conexión LDAP cerrada.");
        }
    }
}
