<?php

namespace App\Services;

use App\Entities\ApiToken;
use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class AuthService
{
    /** Hashed with the same algorithm and cost as real passwords */
    private static ?string $dummyHash = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $users,
        private readonly ApiTokenRepository $tokens,
        private readonly int $tokenTtl,
    ) {
    }

    /**
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): IssuedToken
    {
        $user = $this->users->findOneByEmail($email);

        if ($user === null) {
            // Spend the same time as for a wrong password, so response times don't reveal which emails exist
            password_verify($password, self::$dummyHash ??= password_hash('dummy-password', PASSWORD_DEFAULT));

            throw new InvalidCredentialsException();
        }

        if (!$user->verifyPassword($password)) {
            throw new InvalidCredentialsException();
        }

        if ($user->passwordNeedsRehash()) {
            $user->changePassword($password);
        }

        $plainToken = bin2hex(random_bytes(32));
        $token = new ApiToken($user, $plainToken, new DateTimeImmutable("+{$this->tokenTtl} seconds"));

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return new IssuedToken($token, $plainToken);
    }

    /**
     * Returns the token if it exists and has not expired. Expired tokens are deleted.
     */
    public function authenticate(string $plainToken): ?ApiToken
    {
        $token = $this->tokens->findOneByPlainToken($plainToken);

        if ($token !== null && $token->isExpired()) {
            $this->logout($token);

            return null;
        }

        return $token;
    }

    public function logout(ApiToken $token): void
    {
        $this->entityManager->remove($token);
        $this->entityManager->flush();
    }
}
