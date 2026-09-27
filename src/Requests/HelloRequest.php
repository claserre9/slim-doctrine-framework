<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of GET /api.
 */
final class HelloRequest
{
    public function __construct(
        #[Assert\Length(min: 2, minMessage: 'Name must be at least 2 characters long')]
        public readonly ?string $name = null,
    ) {
    }
}
