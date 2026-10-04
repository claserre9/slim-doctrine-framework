<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of POST /users.
 */
final class CreateUserRequest
{
    public function __construct(
        #[Assert\NotBlank(normalizer: 'trim'), Assert\Email(normalizer: 'trim'), Assert\Length(max: 180, normalizer: 'trim')]
        public readonly string $email,
        #[Assert\NotBlank(normalizer: 'trim'), Assert\Length(max: 100)]
        public readonly string $name,
        // bcrypt only uses the first 72 bytes of a password
        #[Assert\Length(min: 8, max: 72)]
        public readonly string $password,
    ) {
    }
}
