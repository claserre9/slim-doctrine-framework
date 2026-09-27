<?php

namespace App\Middleware;

use App\Validation\Validator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Validates query params and parsed body against the given rules. On success,
 * the validated input is available in the `validated` request attribute.
 */
final class ValidationMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, string|list<string>> $rules
     */
    public function __construct(
        private readonly array $rules,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $this->getInputData($request);
        $validator = new Validator();

        if (!$validator->validate($data, $this->rules)) {
            $response = $this->responseFactory->createResponse(422);
            $response->getBody()->write(json_encode(
                ['error' => ['code' => 422, 'message' => 'Validation failed', 'details' => $validator->errors()]],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));

            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request->withAttribute('validated', $data));
    }

    /**
     * @return array<string, mixed>
     */
    private function getInputData(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();

        return is_array($parsed)
            ? array_merge($request->getQueryParams(), $parsed)
            : $request->getQueryParams();
    }
}
