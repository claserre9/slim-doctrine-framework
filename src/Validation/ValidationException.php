<?php

namespace App\Validation;

use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpSpecializedException;

/**
 * Invalid request input. Rendered as a 422 by JsonErrorHandler, with the
 * errors always included (they describe the client's input, not internals).
 */
final class ValidationException extends HttpSpecializedException
{
    /** @var int */
    protected $code = 422;

    /** @var string */
    protected $message = 'Unprocessable Entity.';

    protected string $title = '422 Unprocessable Entity';

    protected string $description = 'The request contains invalid data.';

    /**
     * @param array<string, list<string>> $errors field name => error messages
     */
    public function __construct(
        ServerRequestInterface $request,
        private readonly array $errors,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
