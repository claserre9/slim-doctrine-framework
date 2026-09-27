<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Must be the outermost middleware so that preflight requests are answered
 * before routing, and error responses also carry CORS headers.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array{
     *     origins: list<string>,
     *     methods: list<string>,
     *     headers: list<string>,
     *     expose_headers: list<string>,
     *     credentials: bool,
     *     max_age: int
     * } $settings
     */
    public function __construct(
        private readonly array $settings,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $isPreflight = $request->getMethod() === 'OPTIONS'
            && $request->hasHeader('Access-Control-Request-Method');

        $response = $isPreflight
            ? $this->responseFactory->createResponse(204)
            : $handler->handle($request);

        return $this->withCorsHeaders($response, $request->getHeaderLine('Origin'), $isPreflight);
    }

    private function withCorsHeaders(ResponseInterface $response, string $origin, bool $isPreflight): ResponseInterface
    {
        $response = $response->withAddedHeader('Vary', 'Origin');

        $allowOrigin = $this->resolveAllowedOrigin($origin);
        if ($allowOrigin === null) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);

        if ($this->settings['credentials']) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->settings['expose_headers'] !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->settings['expose_headers']));
        }

        if ($isPreflight) {
            $response = $response
                ->withHeader('Access-Control-Allow-Methods', strtoupper(implode(', ', $this->settings['methods'])))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->settings['headers']))
                ->withHeader('Access-Control-Max-Age', (string) $this->settings['max_age']);
        }

        return $response;
    }

    /**
     * Returns the Access-Control-Allow-Origin value, or null when the origin is not allowed.
     */
    private function resolveAllowedOrigin(string $origin): ?string
    {
        $allowed = $this->settings['origins'];

        if (in_array('*', $allowed, true)) {
            // A wildcard is not allowed together with credentials: echo the origin instead
            return $this->settings['credentials'] && $origin !== '' ? $origin : '*';
        }

        return $origin !== '' && in_array($origin, $allowed, true) ? $origin : null;
    }
}
