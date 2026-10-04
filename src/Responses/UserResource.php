<?php

namespace App\Responses;

use App\Entities\User;
use DateTimeInterface;

/**
 * Public representation of a user. Never exposes the password hash.
 */
final class UserResource
{
    /**
     * @return array{id: int, email: string, name: string, created_at: string, updated_at: string}
     */
    public static function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'created_at' => $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updated_at' => $user->getUpdatedAt()->format(DateTimeInterface::ATOM),
        ];
    }
}
