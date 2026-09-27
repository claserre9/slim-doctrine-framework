<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of GET /api.
 */
final class HelloRequest
{
    public function __construct(
        #[Assert\Length(min: 2)]
        public readonly ?string $name = null,
    ) {
    }
}
