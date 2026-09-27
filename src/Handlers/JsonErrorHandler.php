<?php

namespace App\Handlers;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\ErrorHandler;
use Throwable;

/**
 * Renders every error as JSON. Exception details are only exposed when
 * displayErrorDetails is enabled (debug mode).
 */
final class JsonErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        $error = [
            'code' => $this->statusCode,
            'message' => $exception instanceof HttpException
                ? $exception->getTitle()
                : 'Internal Server Error',
        ];

        if ($this->displayErrorDetails) {
            $error['details'] = [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
                'trace' => $this->formatTrace($exception),
            ];
        }

        $response = $this->responseFactory->createResponse($this->statusCode);
        $response->getBody()->write(
            json_encode(['error' => $error], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        $response = $response->withHeader('Content-Type', 'application/json');

        if ($exception instanceof HttpMethodNotAllowedException) {
            $response = $response->withHeader('Allow', implode(', ', $exception->getAllowedMethods()));
        }

        return $response;
    }

    /**
     * @return list<array{file: ?string, line: ?int, function: string, class: ?string}>
     */
    private function formatTrace(Throwable $exception): array
    {
        return array_map(static fn (array $frame): array => [
            'file' => $frame['file'] ?? null,
            'line' => $frame['line'] ?? null,
            'function' => $frame['function'],
            'class' => $frame['class'] ?? null,
        ], $exception->getTrace());
    }
}
