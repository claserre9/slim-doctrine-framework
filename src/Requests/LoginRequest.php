<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of POST /auth/login.
 */
final class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $email,
        #[Assert\NotBlank]
        public readonly string $password,
    ) {
    }
}
