<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody(); //get data from form
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        $errors = []; //for handling errors

        //check if every field was answered
        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        }elseif(strlen($username) < 4){ 
            $errors['username'] = 'Username must contain 4 characters';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8 || !preg_match('/\d/', $password)) //check if it meets requirements
        {
            $errors['password'] = 'Password must be at least 8 characters and contain a number.';
        }

        //check if theyre the same
        if (empty($passwordConfirm)) {
            $errors['password_confirm'] = 'Please confirm your password.';
        } elseif ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }

        try {
            $this->authService->register($username, $password); //call on the register function
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Registration successful!',
                'icon' => 'check-circle'
            ];
            return $response->withHeader('Location', '/login')->withStatus(302);
        } catch (Exception $e) {
            $this->logger->error('Registration failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Registration failed.',
                'icon' => 'x-circle'
            ];
            $errors['general'] = 'Registration failed. This username is already taken.';
            return $this->render($response, 'auth/register.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {

        //same logic
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username is required.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            return $this->render($response, 'auth/login.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }

        try {
            $user = $this->authService->attempt($username, $password); //attempt at login
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Welcome back! You have been successfully logged in.'
            ];
            return $response->withHeader('Location', '/')->withStatus(302);
        } catch (Exception $e) {
            $this->logger->error('Login failed: ' . $e->getMessage());
            $errors['general'] = 'Invalid username or password.';
            return $this->render($response, 'auth/login.twig', [
                'errors' => $errors,
                'username' => $username
            ]);
        }
    }

    public function logout(Request $request, Response $response): Response
    {
        //simple session destroy
        session_unset();
        session_destroy();
       
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
