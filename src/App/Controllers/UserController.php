<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\UserCollection;
use Paw\App\Models\MailjetMailer;
use Paw\App\Models\GoogleClient;
use Paw\App\Models\LDAP;
use Paw\Core\Traits\Loggable;

use Paw\App\Models\DependenciasCollection;
use Paw\App\Models\RolesCollection;

use Exception;

class UserController extends Controller
{
    use Loggable;
    public $ldap;
    public ?string $modelName = UserCollection::class;    
    public $dependencia;

    public function __construct()
    {
        global $log, $config; 

        parent::__construct();     

        $this->ldap = new LDAP($config);

        $this->dependencia = new DependenciasCollection($log, $this->qb);


        $this->setLogger($log);

        $this->menu = $this->adjustMenuForSession($this->menu);  
    }

    public function adjustMenuForSession($menu) {

        // $this->logger->info("dentro de adjustMenuForSession: ", [$menu]);

        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Si hay sesion actva elimino path de login y register
        if (isset($_SESSION['nombre_usuario'])) {
            // Filtrar los elementos del menú
            $menu['menu'] = array_filter($menu['menu'], function ($item) {
                return !in_array($item['href'], ['/user/login', '/user/register' ]);
            });
            $menu['rol_usuario'] = $this->getRolUsuario();
            $menu['icono_rol'] = $this->getIconoRol();
        } else {
            // Si no hay sesion entonces saco del menu las opciones para usuarios logueados
            $menu['menu'] = array_filter($menu['menu'], function ($item) {
                return !in_array($item['href'], ['/user/logout', '/user/ver-perfil', 
                                                '/orden-de-trabajo/listar', '/orden-de-trabajo/nuevo', 
                                                '/minuta/new','/user/login', '/user/register', 
                                                '/minutas/listar', '/talleres/ver_talleres', '/facturacion/listar', '/facturacion/new' ]);
                // return $item['href'] !== '/user/logout';
            });
        }

        $this->logger->debug("menu: ", [$menu]);

        return $menu;
    }    

    public function getRolUsuario()
    {
        return $_SESSION['usuario_rol'];
    }

    public function getIconoRol()
    {
        return $_SESSION['icono_rol'];
    }    

    public function verificarSesion() {
        if (!$this->haySession()) {
            redirect('user/login');
        }
    }

    public function getAccount()
    {
        return $_SESSION['account'];
    }

    public function haySession()
    {
        return (session_status() == PHP_SESSION_ACTIVE) && isset($_SESSION['nombre_usuario']); 
    }


    public function getListado()
    {
        $this->logger->info('📥 UsuariosController::getListado() - Inicio');

        try {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            $search = trim($_GET['search'] ?? '');

            $this->logger->debug("🔎 Filtros recibidos", ['page' => $page, 'search' => $search]);

            $usuarios = $this->model->buscarUsuarios($search, $limit, $offset);
            $total = $this->model->contarUsuarios($search);

            $listadoRoles = new RolesCollection($this->logger, $this->qb);

            $roles = $listadoRoles->getRoles();

            if ($isAjax) {
                $this->logger->info('✅ UsuariosController::getListado() - Respuesta AJAX enviada', ['total' => $total]);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'usuarios' => $usuarios,
                    'total' => $total,
                    'limit' => $limit,
                    'roles' => $roles,
                    'currentPage' => $page
                ]);
                return;
            }

            // Vista completa (no AJAX)
            return view('usuarios.listado', array_merge(
                ['usuarios' => $usuarios],
                $this->menu
            ));

        } catch (\Exception $e) {
            $this->logger->error('❌ UsuariosController::getListado() - Error en listado', [
                'mensaje' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Error interno del servidor.'
                ]);
                return;
            }

            return view('error', ['mensaje' => 'Ocurrió un error al cargar los usuarios.']);
        }
    }


    public function confirmarSolicitudDependencia()
    {
        $this->logger->info('📥 UsuariosController::confirmarSolicitudDependencia() - Inicio');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $usuarioId = $data['usuario_id'] ?? null;
            $obs = $data['observaciones'] ?? '';

            if (!$usuarioId) {
                throw new Exception("Falta el ID del usuario.");
            }

            $resultado = $this->model->confirmarAsignacionDeDependencia($usuarioId, $obs);

            if (!$resultado['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => $resultado['motivo'] ?? 'Motivo desconocido'
                ]);
                return;
            }

            $dependenciaId = $resultado['dependencia_id'];
            $this->setDependenciaId($dependenciaId);

            $DatosDependencia = $this->dependencia->getDependencias($this->getDependenciaId());
            $this->setDescripcionDependencia($DatosDependencia[0]['descripcion'] ?? '');

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al confirmar asignación: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => "Error inesperado: " . $e->getMessage()
            ]);
        }
    }


    public function rechazarSolicitudDependencia()
    {
        $this->logger->info('📥 UsuariosController::rechazarSolicitudDependencia() - Inicio');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $usuarioId = $data['usuario_id'] ?? null;
            $obs = $data['observaciones'] ?? '';

            if (!$usuarioId) {
                throw new Exception("Falta el ID del usuario.");
            }

            $this->model->rechazarAsignacionDeDependencia($usuarioId, $obs);

            echo json_encode(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->error("❌ Error al rechazar asignación: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function actualizarRol()
    {
        $this->logger->info('📤 UsuariosController::actualizarRol() - Inicio');

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $usuarioId = $data['usuario_id'] ?? null;
            $nuevoRolId = $data['nuevo_rol'] ?? null;

            if (!$usuarioId || !$nuevoRolId) {
                throw new Exception('Faltan datos obligatorios.');
            }

            $this->model->actualizarRolDeUsuario($usuarioId, $nuevoRolId);

            $this->logger->info("✅ Rol actualizado correctamente para usuario ID $usuarioId");
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error("❌ Error al actualizar rol de usuario: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    public function login()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if ($this->request->method() == 'POST') {
            $this->logger->debug("Entrando al POST Login..");

            $username = htmlspecialchars($this->request->get('username'));
            $password = htmlspecialchars($this->request->get('password'));

            // 1. Autenticación contra LDAP
            $userInfo = $this->ldap->authenticateUser($username, $password);
            $this->logger->debug("UserInfo LDAP: ", [$userInfo]);

            if ($userInfo) {
                // 2. Verificar o insertar usuario en la base local
                $existeUsuario = $this->model->existe($username);

                if (not($existeUsuario[0])) {
                    $this->logger->debug("No existe usuario en la BD", [$existeUsuario]);
                    $nuevoIdUser = $this->model->guardarNuevoAcceso($username, $userInfo);
                } else {
                    $this->logger->debug("Existe usuario en la BD", [$existeUsuario]);
                    $nuevoIdUser = $existeUsuario[1];
                }

                // 3. Preparar info para sesión
                $usuarioDb = $existeUsuario[2];
                $userInfo['id_user'] = $nuevoIdUser;
                $userInfo['rol'] = $usuarioDb['rol'] ?? null;
                $userInfo['icono'] = $usuarioDb['icono'] ?? null;

                

                // Guardar datos adicionales
                $this->setDependenciaId($usuarioDb['dependencia_id']);
                $this->setIdUser($nuevoIdUser);

                $dependencias = new DependenciasCollection($this->logger, $this->qb);
                $DatosDependencia = $dependencias->getDependencias($this->getDependenciaId());
                $this->setDescripcionDependencia($DatosDependencia[0]['descripcion'] ?? '');

                // 4. Cargar sesión
                $parametros = [
                    'id_user' => 'id_user',
                    'nombre_usuario' => 'name',
                    'tipo_usuario' => 'group',
                    'email' => 'email',
                    'account' => 'account',
                    'usuario_rol' => 'rol',
                    'icono_rol' => 'icono'
                ];
                $this->cargarSesion($userInfo, $parametros);

                $this->menu2 = $this->claseMenu->getMenuFiltrado($userInfo['rol'], $this->haySession());
                $this->menu2['rol_usuario'] = $this->getRolUsuario();
                $this->menu2['icono_rol'] = $this->getIconoRol();

                $this->logger->info("🟢 datos menu2 getMenuFiltrado: ", [$this->menu2]);
                $this->logger->info("🟢 Sesión iniciada correctamente", $_SESSION);

                // 5. Redireccionar
                if (isset($_SESSION['redirect_url'])) {
                    $redirectUrl = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    redirect($redirectUrl);
                } else {
                    redirect('');
                }

            } else {
                // Credenciales incorrectas
                $this->logger->warning("🔴 Falló autenticación para usuario: $username");

                $datos = [
                    'error' => 'Usuario o contraseña incorrectos'
                ];

                view('login.view', array_merge(
                    $datos, $this->menu
                ));
            }

        } else {
            // GET → Mostrar vista login
            $this->logger->debug("Entrando al Login..");

            if (!is_null($this->request->getKeySession('redirect_to'))) {
                $_SESSION['redirect_url'] = $this->request->getKeySession('redirect_to');
                $this->logger->debug("Hay redirect_url: ", [$_SESSION['redirect_url']]);
            }

            view('login.view', [
                'authUrl' => "",
                ...$this->menu
            ]);
        }
    }

    function cargarSesion($userInfo, $parametros) {
        foreach ($parametros as $claveSesion => $claveUserInfo) {
            if (isset($userInfo[$claveUserInfo])) {
                $_SESSION[$claveSesion] = $userInfo[$claveUserInfo];
            } else {
                // Opcional: Manejar el caso en que una clave no exista en $userInfo
                $_SESSION[$claveSesion] = null; // O lanzar un error/advertencia según corresponda
                $this->logger->notice("No existe clave en userInfo");
            }
        }
        $this->logger->debug('🧾 Estado actual de $_SESSION:', $_SESSION);

    }

    public function callback()
    {
        global $log;
    
        if (!is_null($this->request->get('code'))) {
            $log->debug("Authorization code recibido: ", [$this->request->get('code')]);
    
            try {
                $googleClient = new GoogleClient();
                $userInfo = $googleClient->receptionCallbacks($this->request->get('code'), $googleClient);
    
                $log->debug("userInfo: ", [$userInfo]);
    
                // Asegúrate de estructurar los datos para la vista
                $data = [
                    'usuario' => [
                        'usuario' => $userInfo->name ?? 'No definido',
                        'email' => $userInfo->email ?? 'No definido',
                        'tipo_usuario' => 'Google User', // Personaliza según sea necesario
                        'imagen' => $userInfo->picture ?? null // Si deseas mostrar la imagen de perfil
                    ]
                ];
    
                view('perfil.view', $data);
    
            } catch (Exception $e) {
                $log->error("Error al recuperar el token de Google: ", [$e->getMessage()]);
                redirect('user/login');
            }
        } else {
            $log->debug("No hay código de autorización");
            redirect('user/login');
        }
    }
    
    public function setDependenciaId($idDep)
    {
        $_SESSION['dependencia_id'] = $idDep;

        if (isset($this->logger)) {
            $this->logger->info("📌 Dependencia asignada a sesión", [
                'dependencia_id' => $idDep
            ]);
        }
    }

    public function getDependenciaId()
    {
        $id = $_SESSION['dependencia_id'] ?? null;

        if (isset($this->logger)) {
            $this->logger->info("📤 Recuperando dependencia de sesión", [
                'dependencia_id' => $id
            ]);
        }

        return $id;
    }


    public function setDescripcionDependencia($descripcion)
    {
        $_SESSION['descripcion_dependencia'] = $descripcion;
    }

    public function getDescripcionDependencia()
    {
        return $_SESSION['descripcion_dependencia'];
    }

    public function getUserType()
    {
        return $_SESSION['tipo_usuario'];
    }

    public function getIdUser()
    {
        return $_SESSION['id_user'];
    }
    public function setIdUser($id)
    {
        $_SESSION['id_user'] = $id;
    }

    public function logout()
    {
        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si hay una sesión activa
        if (isset($_SESSION['nombre_usuario'])) {
            // Cerrar la sesión
            session_unset(); // Eliminar todas las variables de sesión
            session_destroy(); // Destruir la sesión
        }

        // Redirigir a la página de inicio o a la página de login
        redirect('');
    }

    public function register()
    {
        if($this->request->method() == 'POST')
        {

        }else{
            view('register.view', $this->menu);
        }
    }

    public function getUserName()
    {
        return $_SESSION['nombre_usuario'];
    }
    // public function getIdUser()
    // {
    //     return $_SESSION['id_user'];
    // }

    public function getUserEmail()
    {
        return $_SESSION['email'];
    }

    public function verPerfil()
    {
        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Obtener todos los datos del usuario (incluye datos extendidos)
        $user = $this->model->getUserById($this->getIdUser());

        // Armar estructura para la vista
        $datos = [
            'usuario' => [
                'id' => $user['id'],
                'usuario' => $user['usuario'],
                'email' => $user['email'],
                'tipo_usuario' => $user['tipo_usuario'],
                'rol' => $user['rol'],
                'account' => $this->getAccount(),
                'dependencia_descripcion' => $user['dependencia_descripcion'],
                'estado_solicitud' => $user['estado_solicitud'],
                'fecha_solicitud' => $user['fecha_solicitud'],
                'fecha_resolucion' => $user['fecha_resolucion'],
                'observaciones' => $user['observaciones'],
                'imagen' => $user['imagen'] ?? null
            ]
        ];

        // Obtener listado completo de dependencias para el <select>
        $dependenciaCollection = new \Paw\App\Models\DependenciasCollection($this->logger, $this->qb);
        $dependencias = $dependenciaCollection->getDependencias();

        $this->logger->info("👤 Datos de usuario para perfil:", [$datos]);

        view('perfil.view', array_merge(
            $datos + ['dependencias' => $dependencias],
            $this->menu
        ));
    }



    public function asignarDestino()
    {
        $dependencia_id = $this->request->get('dependencia_id');
        $ordenativa_funcion = $this->request->get('ordenativa_funcion');

        // Obtener ID del usuario logueado (desde sesión)
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $usuario_id = $this->getIdUser();

        $this->logger->info("usuario id: ", [$usuario_id, $_SESSION]);

        if (!$usuario_id) {
            echo json_encode(['ok' => false, 'error' => 'Usuario no autenticado']);
            return;
        }

        try {
            $resultado = $this->model->solicitarAsignacionDependencia($usuario_id, $dependencia_id, $ordenativa_funcion);

            if (!$resultado['success']) {
                echo json_encode([
                    'ok' => false,
                    'error' => $resultado['motivo'] ?? 'No se pudo registrar la solicitud'
                ]);
                return;
            }

            // Obtener nombre de la dependencia asignada
            $dependencia = $this->model->getNombrePorId($dependencia_id);

            echo json_encode([
                'ok' => true,
                'nombre_dependencia' => $dependencia
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("Error al asignar dependencia: " . $e->getMessage());
            echo json_encode(['ok' => false, 'error' => 'Error al guardar los datos.']);
        }

        exit;
    }

    
    public function enviarMail()
    {
        try {
            $mailer = new MailjetMailer();
            $result = $mailer->send(
                'juanm.soft@ejemplo.com',
                'Juan',
                'Prueba de Correo',
                'Contenido del correo en texto plano.',
                '<h3>Contenido del correo en HTML</h3>'
            );
        
            echo "Correo enviado exitosamente.";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }        
    }



}