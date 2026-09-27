<?php

namespace App\Controllers;

use App\Requests\HelloRequest;
use App\Validation\RequestMapper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiController
{
    use RespondsWithJson;

    public function __construct(
        private readonly RequestMapper $mapper,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->mapper->map($request, HelloRequest::class);

        return $this->json($response, ['message' => 'Hello ' . ($input->name ?? 'World')]);
    }
}
