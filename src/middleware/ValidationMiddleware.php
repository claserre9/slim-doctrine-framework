<?php

namespace App\middleware;

use App\validation\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class ValidationMiddleware implements MiddlewareInterface
{
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $data = $this->getInputData($request);
        $validator = new Validator();
        if (!$validator->validate($data, $this->rules)) {
            $errors = $validator->errors();
            $payload = json_encode(['error' => ['code' => 422, 'message' => 'Validation failed', 'details' => $errors]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $response = new SlimResponse(422);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
        // Attach validated data for handlers to use
        return $handler->handle($request->withAttribute('validated', $data));
    }

    private function getInputData(Request $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return array_merge($request->getQueryParams(), $parsed);
        }
        return $request->getQueryParams();
    }
}
