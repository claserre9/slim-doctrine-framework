<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of PATCH /users/{id}: omitted (or null) fields are left unchanged.
 */
final class UpdateUserRequest
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, normalizer: 'trim'), Assert\Email(normalizer: 'trim'), Assert\Length(max: 180, normalizer: 'trim')]
        public readonly ?string $email = null,
        #[Assert\NotBlank(allowNull: true, normalizer: 'trim'), Assert\Length(max: 100)]
        public readonly ?string $name = null,
        #[Assert\Length(min: 8, max: 72)]
        public readonly ?string $password = null,
    ) {
    }
}
