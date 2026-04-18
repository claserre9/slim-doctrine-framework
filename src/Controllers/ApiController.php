<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ApiController extends BaseController
{
    public function index(Request $request, Response $response, array $args): ResponseInterface
    {
        return $this->json($response, ['name' => 'Bob', 'age' => 40]);
    }
}
