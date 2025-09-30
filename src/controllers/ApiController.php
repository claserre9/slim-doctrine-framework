<?php

namespace App\controllers;

use Psr\Http\Message\MessageInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class ApiController
{
    public function index(Request $request, Response $response, array $args): Response|MessageInterface
    {
        $data = ['name' => 'Bob', 'age' => 40];
        $payload = json_encode($data, JSON_THROW_ON_ERROR);

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}