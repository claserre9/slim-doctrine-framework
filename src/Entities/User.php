<?php

namespace App\Entities;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;

/**
 * A user account. The entity guarantees its own invariants (valid, normalized
 * email; non-empty name; hashed password), whatever the caller.
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(name: 'password_hash', length: 255)]
    private string $passwordHash;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct(string $email, string $name, string $plainPassword)
    {
        $this->email = self::normalizeEmail($email);
        $this->name = self::normalizeName($name);
        $this->passwordHash = self::hashPassword($plainPassword);
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        return $email;
    }

    public function getId(): int
    {
        return $this->id ?? throw new LogicException('The user has not been persisted yet.');
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeEmail(string $email): void
    {
        $this->email = self::normalizeEmail($email);
        $this->touch();
    }

    public function rename(string $name): void
    {
        $this->name = self::normalizeName($name);
        $this->touch();
    }

    public function changePassword(string $plainPassword): void
    {
        $this->passwordHash = self::hashPassword($plainPassword);
        $this->touch();
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }

    /**
     * True when the hash was made with an older algorithm or cost: rehash it on next login.
     */
    public function passwordNeedsRehash(): bool
    {
        return password_needs_rehash($this->passwordHash, PASSWORD_DEFAULT);
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('The name cannot be empty.');
        }

        return $name;
    }

    private static function hashPassword(string $plainPassword): string
    {
        if ($plainPassword === '') {
            throw new InvalidArgumentException('The password cannot be empty.');
        }

        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
