<?php

namespace App\Services;

use RuntimeException;

final class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
