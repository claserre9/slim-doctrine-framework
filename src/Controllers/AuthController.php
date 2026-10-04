<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Requests\LoginRequest;
use App\Responses\UserResource;
use App\Services\AuthService;
use App\Services\InvalidCredentialsException;
use App\Validation\RequestMapper;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpUnauthorizedException;

final class AuthController
{
    use RespondsWithJson;

    public function __construct(
        private readonly RequestMapper $mapper,
        private readonly AuthService $auth,
    ) {
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->mapper->map($request, LoginRequest::class);

        try {
            $issued = $this->auth->login($input->email, $input->password);
        } catch (InvalidCredentialsException $e) {
            throw new HttpUnauthorizedException($request, $e->getMessage(), $e);
        }

        return $this->json($response, [
            'token' => $issued->plainToken,
            'token_type' => 'Bearer',
            'expires_at' => $issued->token->getExpiresAt()->format(DateTimeInterface::ATOM),
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->logout(AuthMiddleware::token($request));

        return $response->withStatus(204);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, UserResource::toArray(AuthMiddleware::user($request)));
    }
}
