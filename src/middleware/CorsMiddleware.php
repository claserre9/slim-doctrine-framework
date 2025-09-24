<?php

namespace App\middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware implements MiddlewareInterface
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');
        $cors = $this->settings['cors'] ?? [];

        $response = $request->getMethod() === 'OPTIONS'
            ? new SlimResponse(204)
            : $handler->handle($request);

        return $this->applyCors($response, $origin, $cors, $request);
    }

    private function applyCors(Response $response, string $origin, array $cors, Request $request): Response
    {
        $allowedOrigins = $cors['origins'] ?? ['*'];
        $allowedMethods = strtoupper(implode(', ', $cors['methods'] ?? ['GET','POST','PUT','PATCH','DELETE','OPTIONS']));
        $allowedHeaders = implode(', ', $cors['headers'] ?? ['Content-Type','Authorization','Accept','Origin','X-Requested-With']);
        $exposeHeaders = implode(', ', $cors['expose_headers'] ?? []);
        $credentials = !empty($cors['credentials']);
        $maxAge = (string)($cors['max_age'] ?? 600);

        $allowOrigin = '*';
        if ($origin && !in_array('*', $allowedOrigins, true)) {
            foreach ($allowedOrigins as $ao) {
                if ($ao === $origin) {
                    $allowOrigin = $origin;
                    break;
                }
            }
        } elseif (!empty($allowedOrigins)) {
            $allowOrigin = $allowedOrigins[0] ?? '*';
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Access-Control-Max-Age', $maxAge);

        if ($exposeHeaders !== '') {
            $response = $response->withHeader('Access-Control-Expose-Headers', $exposeHeaders);
        }
        if ($credentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // In preflight, ensure 204 without body
        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withStatus(204);
        }

        return $response;
    }
}
