<?php

namespace App\Repositories;

use App\Entities\User;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function find(int $id): ?User
    {
        return $this->entityManager->find(User::class, $id);
    }

    public function findOneByEmail(string $email): ?User
    {
        try {
            $email = User::normalizeEmail($email);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    /**
     * @return array{items: list<User>, total: int}
     */
    public function paginate(int $page, int $limit): array
    {
        $repository = $this->entityManager->getRepository(User::class);

        /** @var list<User> $items */
        $items = $repository->findBy([], ['id' => 'ASC'], $limit, ($page - 1) * $limit);

        return ['items' => $items, 'total' => $repository->count()];
    }
}
