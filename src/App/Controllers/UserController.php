<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\UserCollection;
use Paw\App\Models\MailjetMailer;
use Paw\App\Models\GoogleClient;
use Paw\App\Models\LDAP;
use Paw\Core\Traits\Loggable;

use Exception;

class UserController extends Controller
{
    use Loggable;
    public $ldap;
    public ?string $modelName = UserCollection::class;    

    public function __construct()
    {
        global $log, $config; 

        parent::__construct();     

        $this->ldap = new LDAP($config);

        $this->setLogger($log);

        $this->menu = $this->adjustMenuForSession($this->menu);  
    }

    public function adjustMenuForSession($menu) {

        $this->logger->info("dentro de adjustMenuForSession: ", [$menu]);

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

    public function login()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }     

        if($this->request->method() == 'POST')
        {
            $this->logger->debug("Entrando al POST Login..");

            $username = htmlspecialchars($this->request->get('username'));
            $password = htmlspecialchars($this->request->get('password'));
            /**
             * autentico con la base de datos de Windows Server
             */
            $userInfo =  $this->ldap->authenticateUser($username, $password);   


             $this->logger->debug("UserInfo: ",[$userInfo]);

            if ($userInfo) {

                /**
                 * si el usuario existe en el servidor de windows
                 * lo busco en la tabla interna USUARIOS,
                 * sino existe inserto uno nuevo sino traigo su id
                 * y lo guardo en la sesion
                 */

                $existeUsuario =  $this->model->existe($username);
                if (not($existeUsuario[0])) {
                    $this->logger->debug("No existe usuario en la BD");
                    $nuevoIdUser = $this->model->guardarNuevoAcceso($username, $userInfo);
                }else{
                    $this->logger->debug("Existe usuario en la BD");
                    $nuevoIdUser = $existeUsuario[1];
                };

                $userInfo['id_user'] = $nuevoIdUser;

                $this->setIdUser($nuevoIdUser);

                $parametros = [
                    'id_user' => 'id_user',
                    'nombre_usuario' => 'name',
                    'tipo_usuario' => 'group',
                    'email' => 'email',
                    'account' => 'account'
                ];
                
                $this->cargarSesion($userInfo, $parametros);

                $this->logger->debug("UserInfo: ",[$userInfo]);

                if(isset($_SESSION['redirect_url'])){
                    $this->logger->debug("Hay redirect_url");
                    $redirectUrl = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']);
                    redirect($redirectUrl);
                }else{
                    $this->logger->debug("No Hay redirect_url");
                    redirect('');
                }
            } else {

                $datos = [
                    'error' => 'Usuario o contraseña incorrectos'
                ];

                view('login.view', array_merge(
                    $datos, $this->menu));
            }
        }else{                       
            $this->logger->debug("Entrando al Login..");

            if (!is_null($this->request->getKeySession('redirect_to'))){
                $_SESSION['redirect_url'] = $this->request->getKeySession('redirect_to');

                $this->logger->debug("Hay Redirect_url: ",[$_SESSION['redirect_url']]);
            }

            // $client = new GoogleClient();
            // $authUrl = $client->createAuthUrl();

            // $this->logger->debug("authUrl: ",[$authUrl]);

            view('login.view', [
                // 'authUrl' => $authUrl,
                'authUrl' => "",
                ...$this->menu]);
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
    }

    public function getDependenciaId()
    {
        return $_SESSION['dependencia_id'];
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

        $user = $this->model->getUserById($this->getIdUser());


        $datos = [
            'usuario' => [
                'usuario' => $this->getUserName(),
                'email' => $this->getUserEmail(),
                'tipo_usuario' => $this->getUserType(),
                'account' => $this->getAccount(),
                'account' => $this->getAccount(),
                'dependencia' => $this->model->getNombrePorId($user['dependencia_id'])
            ]
        ];

        $dependenciaCollection = new \Paw\App\Models\DependenciasCollection($this->logger, $this->qb);
        $dependencias = $dependenciaCollection->getDependencias();

        $this->logger->info("datos: ",[$datos]);

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
            // Actualiza la dependencia del usuario
            $this->model->actualizarDependenciaUsuario($usuario_id, $dependencia_id, $ordenativa_funcion);

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