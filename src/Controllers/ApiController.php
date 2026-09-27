<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiController
{
    use RespondsWithJson;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = $request->getAttribute('validated')['name'] ?? 'World';

        return $this->json($response, ['message' => "Hello $name"]);
    }
}
