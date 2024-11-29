<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\UserCollection;
use Paw\App\Models\MailjetMailer;
use Paw\App\Models\GoogleClient;
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
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }     

        if($this->request->method() == 'POST')
        {
            $username = htmlspecialchars($this->request->get('username'));
            $password = htmlspecialchars($this->request->get('password'));
            $user = $this->model->getUserByUsernameAndPassword($username, $password);

            if ($user) {
                $_SESSION['nombre_usuario'] = $user['usuario'];
                $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
                $_SESSION['id_user'] = $user['id'];

                $this->logger->debug("User: ",[$user]);

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
                $error = 'Usuario o contraseña incorrectos';
                view('login.view', [
                    ['error' => $error],
                    ...$this->menu
                ]);
            }
        }else{                       
            $this->logger->debug("Entrando al Login..");

            if (!is_null($this->request->getKeySession('redirect_to'))){
                $_SESSION['redirect_url'] = $this->request->getKeySession('redirect_to');

                $this->logger->debug("Hay Redirect_url: ",[$_SESSION['redirect_url']]);
            }

            $client = new GoogleClient();
            $authUrl = $client->createAuthUrl();

            $this->logger->debug("authUrl: ",[$authUrl]);

            view('login.view', [
                'authUrl' => $authUrl,
                ...$this->menu]);
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