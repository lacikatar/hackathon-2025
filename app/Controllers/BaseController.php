<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class BaseController
{
    public function __construct(
        protected Twig $view,
    ) {}

    protected function render(Response $response, string $template, array $data = []): Response
    {
        // Add flash message to template data if it exists
        if (isset($_SESSION['flash_message'])) {
            $data['session'] = [
                'flash_message' => $_SESSION['flash_message']
            ];
            // Clear the flash message after using it
            unset($_SESSION['flash_message']);
        }

        return $this->view->render($response, $template, $data);
    }

    // TODO: add here any common controller logic and use in concrete controllers 
    //idk what i was supposed to add, maybe it was needed in the csv upload function? or a left out something 
}
