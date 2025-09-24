<?php

namespace App\middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler as SlimErrorHandler;
use Throwable;

class ErrorHandler extends SlimErrorHandler
{
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = $this->getStatusCode();
        $displayErrorDetails = $this->displayErrorDetails;

        $error = [
            'error' => [
                'code' => $statusCode,
                'message' => $this->getErrorTitle($statusCode),
                'details' => [
                    'type' => $this->determineExceptionType($exception),
                    'message' => $exception->getMessage(),
                ],
            ],
        ];

        if ($displayErrorDetails) {
            $error['error']['trace'] = $this->formatTrace($exception);
        }

        $payload = json_encode($error, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $response = $this->responseFactory->createResponse($statusCode);
        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getErrorTitle(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }

    private function determineExceptionType(Throwable $e): string
    {
        $class = get_class($e);
        $parts = explode('\\\\', $class);
        return end($parts) ?: $class;
    }

    private function formatTrace(Throwable $e): array
    {
        return array_map(static function ($t) {
            return [
                'file' => $t['file'] ?? null,
                'line' => $t['line'] ?? null,
                'function' => $t['function'] ?? null,
                'class' => $t['class'] ?? null,
            ];
        }, $e->getTrace());
    }

    private function getStatusCode(): int
    {

        if ($this->method === 'OPTIONS') {
            return 200;
        }

        $exception = $this->exception;

        if ($exception instanceof HttpException) {
            return (int) $exception->getCode();
        }

        return 500;
    }
}
