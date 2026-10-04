<?php

namespace App\Services;

use RuntimeException;

final class EmailAlreadyUsedException extends RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('The email address "%s" is already used.', $email));
    }
}
