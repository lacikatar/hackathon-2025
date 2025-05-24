<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
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
        // TODO: call corresponding service to perform user registration
        $username = $_POST['username'];
        $password = $_POST['password'];
        try{
            $this->authService->register($username, $password);
        }
        catch(\RuntimeException $e){
            $this->logger->error('Registration failed: ' . $e->getMessage());
            return $this->render($response, 'auth/register.twig', ['error' => $e->getMessage()]);
        }

        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures

        $username = $_POST['username'];
        $password = $_POST['password'];
        try{
            $this->authService->attempt($username, $password);
        }
        catch(\RuntimeException $e){
            $this->logger->error(''. $e->getMessage());
            return $this->render($response, 'auth/login.twig', ['error' => $e->getMessage()]);
        }
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
