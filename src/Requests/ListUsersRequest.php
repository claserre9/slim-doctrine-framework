<?php

namespace App\Requests;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query of GET /users.
 */
final class ListUsersRequest
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,
    ) {
    }
}
