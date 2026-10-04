<?php

namespace App\Repositories;

use App\Entities\ApiToken;
use App\Entities\User;
use Doctrine\ORM\EntityManagerInterface;

final class ApiTokenRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findOneByPlainToken(string $plainToken): ?ApiToken
    {
        return $this->entityManager->getRepository(ApiToken::class)
            ->findOneBy(['tokenHash' => ApiToken::hash($plainToken)]);
    }

    /**
     * Deletes the user's tokens, optionally keeping one (e.g. the token of the current request).
     */
    public function deleteForUser(User $user, ?ApiToken $except = null): void
    {
        $query = $this->entityManager->createQueryBuilder()
            ->delete(ApiToken::class, 't')
            ->where('t.user = :user')
            ->setParameter('user', $user);

        if ($except !== null) {
            $query->andWhere('t.id <> :except')->setParameter('except', $except->getId());
        }

        $query->getQuery()->execute();
    }
}
