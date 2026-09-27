<?php

namespace Tests\Handlers;

use App\Handlers\JsonErrorHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Slim\CallableResolver;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Stringable;
use Throwable;

final class JsonErrorHandlerTest extends TestCase
{
    /**
     * @return list<string> the messages logged while handling the exception
     */
    private function handle(Throwable $exception): array
    {
        $logger = new class () extends AbstractLogger {
            /** @var list<string> */
            public array $messages = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->messages[] = (string) $message;
            }
        };

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/');
        $handler = new JsonErrorHandler(new CallableResolver(), new ResponseFactory(), $logger);
        // Same flags as config/bootstrap.php in production: no details displayed, errors logged with details
        $handler($request, $exception, false, true, true);

        return $logger->messages;
    }

    public function testClientErrorsAreNotLogged(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/favicon.ico');

        $this->assertSame([], $this->handle(new HttpNotFoundException($request)));
    }

    public function testServerErrorsAreLogged(): void
    {
        $messages = $this->handle(new \RuntimeException('Database is down'));

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('Database is down', $messages[0]);
    }
}
