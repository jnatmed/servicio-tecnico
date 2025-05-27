<?php

namespace Paw\Core;

use Paw\Core\Request;

class Menu
{
    // Estructura del menú cargada desde el JSON
    protected array $estructura = [];

    // Rol raíz que puede ver todo, definido en "config" del JSON
    protected string $rolRoot;

    // Valor por defecto para el campo 'auth' si no está definido en un ítem
    protected bool $authPorDefecto = true;

    // Rol actual del usuario, seteado manualmente desde UserController
    protected ?string $rolUsuario = null;

    // Estado de sesión (true si el usuario está logueado)
    protected bool $estaLogueado = false;

    protected Request $request;

    /**
     * Constructor de la clase.
     * Carga el archivo menu.json, valida que tenga la config necesaria,
     * y aplica herencia de roles y autenticación.
     */
    public function __construct(string $rutaJson = __DIR__ . '/menu.json')
    {
        $datos = json_decode(file_get_contents($rutaJson), true);

        // Validar que el JSON contenga rol_root en su config
        if (!isset($datos['config']['rol_root'])) {
            throw new \Exception("Falta 'rol_root' en la sección 'config' del archivo de menú.");
        }

        $this->rolRoot = $datos['config']['rol_root'];
        $this->authPorDefecto = (bool)($datos['config']['auth'] ?? true);
        $this->estructura = ['menu' => $datos['menu']];

        $this->aplicarHerencia();

        $this->request = new Request();
    }

    /**
     * Setea el rol actual del usuario (ej: 'tecnica', 'oficina', etc).
     * Esto permite filtrar el menú más adelante.
     */
    public function setRolUsuario(string $rol): void
    {
        $this->rolUsuario = $rol;
    }

    /**
     * Setea si el usuario está logueado o no.
     */
    public function setEstadoSesion(bool $logueado): void
    {
        $this->estaLogueado = $logueado;
    }

    /**
     * Aplica herencia de propiedades 'roles' y 'auth' desde el ítem padre
     * a sus submenús. Si no están definidos, se usan valores por defecto.
     */
    protected function aplicarHerencia(): void
    {
        foreach ($this->estructura['menu'] as &$item) {
            $rolesPadre = $item['roles'] ?? [$this->rolRoot];
            $authPadre = $item['auth'] ?? $this->authPorDefecto;

            $item['roles'] = $rolesPadre;
            $item['auth'] = $authPadre;

            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as &$subitem) {
                    $subitem['roles'] = $subitem['roles'] ?? $rolesPadre;
                    $subitem['auth'] = $subitem['auth'] ?? $authPadre;
                }
            }
        }
    }

    /**
     * Verifica si un ítem del menú puede ser accedido por el usuario actual,
     * según su rol y estado de sesión.
     */
    protected function tienePermiso(array $item, string $rolUsuario, bool $estaLogueado): bool
    {
        // Si requiere autenticación y no está logueado, no lo mostramos
        if (($item['auth'] ?? true) && !$estaLogueado) {
            return false;
        }

        // Obtener roles permitidos o asumir que solo el rol raíz puede verlo
        $roles = $item['roles'] ?? [$this->rolRoot];

        // Si está permitido para todos los roles
        if (in_array('all', $roles)) {
            return true;
        }

        // Si tiene exclusiones con all_less
        foreach ($roles as $rol) {
            if (str_starts_with($rol, 'all_less:')) {
                $prohibidos = array_map('trim', explode(',', str_replace('all_less:', '', $rol)));
                return !in_array($rolUsuario, $prohibidos) || $rolUsuario === $this->rolRoot;
            }
        }

        // Caso normal: rol permitido o es el rol_root
        return in_array($rolUsuario, $roles) || $rolUsuario === $this->rolRoot;
    }

    /**
     * Devuelve la estructura del menú filtrada,
     * según el rol y login actual del usuario.
     * Si no se pasan parámetros, se usan los seteados con setRolUsuario y setEstadoSesion.
     */
    public function getMenuFiltrado(?string $rolUsuario = null, ?bool $estaLogueado = null): array
    {
        $rolUsuario = $rolUsuario ?? $this->rolUsuario ?? '';
        $estaLogueado = $estaLogueado ?? $this->estaLogueado;

        $filtrado = [];

        foreach ($this->estructura['menu'] as $item) {
            // Filtrar submenús según permisos
            if (isset($item['submenu'])) {
                $item['submenu'] = array_filter($item['submenu'], function ($subitem) use ($rolUsuario, $estaLogueado) {
                    return $this->tienePermiso($subitem, $rolUsuario, $estaLogueado);
                });

                // Si no quedan submenús y no tiene href propio, lo descartamos
                if (empty($item['submenu']) && !isset($item['href'])) {
                    continue;
                }
            }

            // Incluir el ítem principal si tiene permiso
            if ($this->tienePermiso($item, $rolUsuario, $estaLogueado)) {
                $filtrado[] = $item;
            }
        }

        // Armar respuesta
        $menuReturn = [
            'menu' => $filtrado,
            'rol_usuario' => $this->request->getKeySession('usuario_rol'),
            'icono_rol' => $this->request->getKeySession('icono_rol')
        ];

        return $menuReturn;
    }
}
