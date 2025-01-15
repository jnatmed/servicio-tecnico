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
            $log->error("No se pudo conectar al servidor LDAP: {$this->ldap_host}:{$this->ldap_port}");
            throw new Exception("No se pudo conectar al servidor LDAP.");
        }

        // Configurar opciones LDAP
        ldap_set_option($this->ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3); // Usar LDAPv3
        ldap_set_option($this->ldap_conn, LDAP_OPT_REFERRALS, 0); // Desactivar referencias
        $log->info("Conexión exitosa al servidor LDAP: {$this->ldap_host}:{$this->ldap_port}");
    }

    public function authenticateAdmin()
    {
        global $log;
        // Intentar hacer el bind con el usuario administrador
        $bind = ldap_bind($this->ldap_conn, $this->ldap_user, $this->ldap_pass);

        if (!$bind) {
            $error = ldap_error($this->ldap_conn);
            $log->error("No se pudo autenticar al servidor LDAP con el usuario: {$this->ldap_user}. Error: $error");
            throw new Exception("No se pudo autenticar al servidor LDAP.");
        }

        $log->info("Autenticación exitosa con el usuario: {$this->ldap_user}");
    }

    public function findUserByUsername($username)
    {
        global $log;

        $search_filter = "(samaccountname=$username)";
        $attributes = ["dn", "cn", "samaccountname"];
        
        $search = ldap_search($this->ldap_conn, $this->base_dn, $search_filter);

        if (!$search) {
            $error = ldap_error($this->ldap_conn);
            $log->error("Error al realizar la búsqueda LDAP: $error");
            throw new Exception("Error al realizar la búsqueda LDAP.");
        }

        $entries = ldap_get_entries($this->ldap_conn, $search);

        if ($entries["count"] > 0) {
            $user_dn = $entries[0]["dn"];
            // Extraer datos específicos
            $name = $entries[0]["cn"][0] ?? null; // Nombre (cn)
            $account = $entries[0]["samaccountname"]["0"]; // Nombre (user account)
            $email = $entries[0]["userprincipalname"][0] ?? null; // Correo electrónico (userPrincipalName)

            // Extraer el primer grupo específico de "memberof" (ejemplo: DEPARTAMENTO DE ASISTENCIA TECNICA)
            $memberof = $entries[0]["memberof"] ?? [];
            $group_name = null;
            foreach ($memberof as $key => $value) {
                if (is_string($value) && preg_match('/^CN=([^,]+)/', $value, $matches)) {
                    $group_name = $matches[1]; // Captura solo el valor después de "CN="
                    break;
                }
            }

            $log->info("Usuario encontrado. DN: $user_dn", $entries[0]);

            return [
                'name' => $name,
                'account' => $account,
                'group' => $group_name,
                'email' => $email,
                'dn' => $user_dn,
            ];
        } else {
            $log->warning("No se encontró al usuario '$username' en el directorio LDAP.");
            return null;
        }
    }

    public function authenticateUser($username, $password)
    {
        global $log;

        $user_info = $this->findUserByUsername($username);

        if (!$user_info) {
            return false; // Usuario no encontrado
        }

        $bind = ldap_bind($this->ldap_conn, $user_info['dn'], $password);

        if ($bind) {
            $log->info("Autenticación exitosa para el usuario: " . $user_info['name'], [$user_info]);
            return $user_info;
        } else {
            $error = ldap_error($this->ldap_conn);
            $log->error("No se pudo autenticar al usuario '$username'. Error: $error");
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
