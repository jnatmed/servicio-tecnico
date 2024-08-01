<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\UserCollection;
use Paw\App\Models\MailjetMailer;
use Paw\Core\Traits\Loggable;

use Exception;

class UserController extends Controller
{
    use Loggable;
    public ?string $modelName = UserCollection::class;    

    public function __construct()
    {
        global $log; 

        parent::__construct();     

        $this->setLogger($log);

        $this->menu = $this->adjustMenuForSession($this->menu);  
    }

    public function adjustMenuForSession($menu) {

        
        $this->logger->info("dentro de adjustMenuForSession: ", [$menu]);

        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar si hay una sesión activa
        if (isset($_SESSION['nombre_usuario'])) {
            // Filtrar los elementos del menú
            $menu['menu'] = array_filter($menu['menu'], function ($item) {
                return !in_array($item['href'], ['/user/login', '/user/register' ]);
            });
        } else {
            // Filtrar los elementos del menú para eliminar 'LOGOUT'
            $menu['menu'] = array_filter($menu['menu'], function ($item) {
                return !in_array($item['href'], ['/user/logout', '/user/ver-perfil', '/orden-de-trabajo/listar', '/orden-de-trabajo/nuevo' ]);
                // return $item['href'] !== '/user/logout';
            });
        }

        $this->logger->debug("menu: ", [$menu]);
        return $menu;
    }    

    public function haySession()
    {
        return (session_status() == PHP_SESSION_ACTIVE) && isset($_SESSION['id_user']); 
    }

    public function login()
    {
        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }     

        if($this->request->method() == 'POST')
        {
            /**
             * recibo user y login y consulto a userCollection 
             * si existe el usuario
             * entonces guardo en $_session: nombre_usuario y tipo_usuario
             */
            $username = htmlspecialchars($this->request->get('username'));
            $password = htmlspecialchars($this->request->get('password'));
            // Verificar credenciales
            $user = $this->model->getUserByUsernameAndPassword($username, $password);

            if ($user) {
                // Usuario autenticado correctamente
                $_SESSION['nombre_usuario'] = $user['usuario'];
                $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
                $_SESSION['id_user'] = $user['id'];
                // Redirigir a una página de inicio o a donde necesites
                // Ejemplo: redirigir al dashboard
                redirect('');
            } else {
                // Usuario o contraseña incorrectos
                // Puedes manejar el error de autenticación aquí
                // Por ejemplo, mostrar un mensaje de error en la vista de login
                $error = 'Usuario o contraseña incorrectos';
                view('login.view', [
                    ['error' => $error],
                    ...$this->menu
            ]);
            }
        }else{                                 
            view('login.view', $this->menu);
        }
    }

    public function getUserType()
    {
        return $_SESSION['tipo_usuario'];
    }

    public function getIdUser()
    {
        return $_SESSION['id_user'];
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

    public function verPerfil()
    {
        // Iniciar la sesión si no está ya iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Suponiendo que `getIdUser` es un método que devuelve el ID del usuario actual
        $userId = $this->getIdUser();  
        $datos = $this->model->getUserById($userId);
        
        $this->logger->info("datos: ",[$datos]);

        view('perfil.view', array_merge([
            'usuario' => $datos
        ], $this->menu));
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