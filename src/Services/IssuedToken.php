<?php

namespace App\Services;

use App\Entities\ApiToken;

/**
 * A token just issued at login: the plain value is only known at this moment.
 */
final class IssuedToken
{
    public function __construct(
        public readonly ApiToken $token,
        public readonly string $plainToken,
    ) {
    }
}
