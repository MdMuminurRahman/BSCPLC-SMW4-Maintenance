<?php
namespace App\Controllers;

use App\Models\User;

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = $this->model('User');
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validate CSRF token
            $this->validateCsrf();
            
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

            $user = $this->userModel->findUserByEmail($email);

            if ($user && password_verify($password, $user->password)) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');
                $_SESSION['last_activity'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

                $this->redirect('');
            } else {
                $this->setFlash('error', 'Invalid credentials');
                $this->view('auth/login', ['email' => $email]);
            }
        } else {
            $this->view('auth/login');
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Validate CSRF token
            $this->validateCsrf();
            
            $data = [
                'name' => htmlspecialchars(
                    filter_input(INPUT_POST, 'name', FILTER_UNSAFE_RAW),
                    ENT_QUOTES,
                    'UTF-8'
                ),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'password' => password_hash(
                    filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW),
                    PASSWORD_DEFAULT,
                    ['cost' => 12]
                )
            ];

            if ($this->userModel->createUser($data)) {
                $this->setFlash('success', 'Registration successful. Please login.');
                $this->redirect('login');
            } else {
                $this->setFlash('error', 'Registration failed');
                $this->view('auth/register', ['email' => $data['email'], 'name' => $data['name']]);
            }
        } else {
            $this->view('auth/register');
        }
    }

    public function logout() {
        // Clear all session data
        $_SESSION = [];
        
        // Get session parameters
        $params = session_get_cookie_params();
        
        // Delete the session cookie
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
        
        // Destroy the session
        session_destroy();
        
        $this->redirect('login');
    }
}