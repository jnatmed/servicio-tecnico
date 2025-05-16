<?php

namespace Paw\Core;

use Paw\App\Models\UserCollection;
use Paw\App\Models\LDAP;
use Paw\Core\Model;

class Session extends Model
{
    protected $userModel;

    public function __construct()
    {
        global $log;
        parent::setLogger($log);
        $this->start();
        $this->userModel = new UserCollection(); 
    }

    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // public function login(...) {
    //     // Este método está reservado para uso futuro.
    //     // Actualmente el login se maneja desde UserController@login()
    // }
    // {
    //     try {
    //         $config = new Config();
    //         $ldap = new \Paw\App\Models\LDAP($config);

    //         $userLdap = $ldap->authenticateUser($usuario, $clave);
    //         if (!$userLdap) {
    //             $this->logger->warning("❌ Falló autenticación LDAP para usuario '$usuario'");
    //             return false;
    //         }


    //         [$existe, $usuarioId, $usuarioDb] = $this->userModel->existe($usuario);
    //         if ($existe && $usuarioDb) {
    //             $_SESSION['usuario_id'] = $usuarioDb['id'];
    //             $_SESSION['usuario_rol'] = $usuarioDb['rol_id'] ?? null;
    //             $_SESSION['usuario'] = $usuarioDb['usuario'];
    //             $_SESSION['usuario_correo'] = $userLdap['email'] ?? $userLdap['userprincipalname'] ?? null;
    //             $_SESSION['usuario_tipo'] = $usuarioDb['tipo_usuario'] ?? null;
    //             $_SESSION['usuario_nombre_completo'] = $userLdap['name'] ?? $userLdap['cn'] ?? null;
    //             $_SESSION['usuario_grupo'] = $userLdap['group'] ?? null;

    //             $this->logger->info("✅ Sesión iniciada correctamente para el usuario '$usuario'");
    //             return true;
    //         }

    //         $this->logger->warning("⚠️ Usuario '$usuario' autenticado en LDAP pero no encontrado en la base");
    //         return false;

    //     } catch (\Exception $e) {
    //         $this->logger->error("🛑 Error en Session::login(): " . $e->getMessage());
    //         return false;
    //     }
    // }


    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['id_user']);
    }

    public function checkAuth(): bool
    {
        return $this->isLoggedIn();
    }

    public function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }
}
