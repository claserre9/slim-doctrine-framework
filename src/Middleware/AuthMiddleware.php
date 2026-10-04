<?php

namespace App\Middleware;

use App\Entities\ApiToken;
use App\Entities\User;
use App\Services\AuthService;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

/**
 * Requires a valid `Authorization: Bearer <token>` header. The authenticated
 * user and token are then available with AuthMiddleware::user() / ::token().
 */
final class AuthMiddleware implements MiddlewareInterface
{
    private const TOKEN_ATTRIBUTE = 'auth.token';

    public function __construct(
        private readonly AuthService $auth,
    ) {
    }

    public static function token(ServerRequestInterface $request): ApiToken
    {
        $token = $request->getAttribute(self::TOKEN_ATTRIBUTE);

        if (!$token instanceof ApiToken) {
            throw new LogicException('No authenticated user: is the route protected by AuthMiddleware?');
        }

        return $token;
    }

    public static function user(ServerRequestInterface $request): User
    {
        return self::token($request)->getUser();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!preg_match('/^Bearer\s+(\S+)$/i', $request->getHeaderLine('Authorization'), $matches)) {
            throw new HttpUnauthorizedException($request, 'Missing bearer token.');
        }

        $token = $this->auth->authenticate($matches[1]);

        if ($token === null) {
            throw new HttpUnauthorizedException($request, 'Invalid or expired token.');
        }

        return $handler->handle($request->withAttribute(self::TOKEN_ATTRIBUTE, $token));
    }
}
