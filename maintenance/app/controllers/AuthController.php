<?php
class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        $this->userModel = $this->model('User');
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'];

            $user = $this->userModel->findUserByEmail($email);

            if ($user && password_verify($password, $user->password)) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_name'] = $user->name;
                $this->redirect('');
            } else {
                $this->setFlash('error', 'Invalid credentials');
                $this->view('auth/login');
            }
        } else {
            $this->view('auth/login');
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
            ];

            if ($this->userModel->createUser($data)) {
                $this->setFlash('success', 'Registration successful. Please login.');
                $this->redirect('login');
            } else {
                $this->setFlash('error', 'Registration failed');
                $this->view('auth/register');
            }
        } else {
            $this->view('auth/register');
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('login');
    }
}