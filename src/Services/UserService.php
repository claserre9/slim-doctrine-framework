<?php

namespace App\Services;

use App\Entities\ApiToken;
use App\Entities\User;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $tokens,
    ) {
    }

    /**
     * @throws EmailAlreadyUsedException
     */
    public function create(string $email, string $name, string $password): User
    {
        $this->assertEmailAvailable($email);

        $user = new User($email, $name, $password);
        $this->entityManager->persist($user);
        $this->flush($email);

        return $user;
    }

    /**
     * Null values are left unchanged. Changing the password revokes the user's
     * other tokens, so that a stolen session does not survive it.
     *
     * @throws EmailAlreadyUsedException
     */
    public function update(User $user, ?string $email, ?string $name, ?string $password, ?ApiToken $currentToken = null): User
    {
        if ($email !== null && User::normalizeEmail($email) !== $user->getEmail()) {
            $this->assertEmailAvailable($email);
            $user->changeEmail($email);
        }

        if ($name !== null) {
            $user->rename($name);
        }

        if ($password !== null) {
            $user->changePassword($password);
            $this->tokens->deleteForUser($user, except: $currentToken);
        }

        $this->flush($user->getEmail());

        return $user;
    }

    public function delete(User $user): void
    {
        $this->tokens->deleteForUser($user);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    private function assertEmailAvailable(string $email): void
    {
        if ($this->users->findOneByEmail($email) !== null) {
            throw new EmailAlreadyUsedException($email);
        }
    }

    /**
     * The unique index catches the race between the availability check and the insert.
     */
    private function flush(string $email): void
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EmailAlreadyUsedException($email);
        }
    }
}
