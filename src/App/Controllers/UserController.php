<?php 

namespace Paw\App\Controllers;

use Paw\Core\Controller;
use Paw\App\Models\UserCollection;
use Paw\Core\Traits\Loggable;


class UserController extends Controller
{
    use Loggable;

    public function login()
    {
        if($this->request->method() == 'POST')
        {
            /**
             * recibo user y login y consulto a userCollection 
             * si existe el usuario
             * entonces guardo en $_session: nombre_usuario y tipo_usuario
             */
            // Verificar credenciales
            $user = $this->userCollection->getUserByUsernameAndPassword($username, $password);

            if ($user) {
                // Usuario autenticado correctamente
                $_SESSION['nombre_usuario'] = $user['usuario'];
                $_SESSION['tipo_usuario'] = $user['tipo_usuario'];

                // Redirigir a una página de inicio o a donde necesites
                // Ejemplo: redirigir al dashboard
                redirect('Location: /');
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
    public function logout()
    {
        /**
         * si hay sesion iniciada
         * la cierro
         */
    }
    public function register()
    {
        if($this->request->method() == 'POST')
        {

        }else{
            view('register.view', $this->menu);
        }
    }
}