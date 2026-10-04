<?php

namespace App\Entities;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * A bearer token issued at login. Only a SHA-256 hash of the token is stored:
 * a database leak does not expose usable tokens.
 */
#[ORM\Entity]
#[ORM\Table(name: 'api_tokens')]
#[ORM\UniqueConstraint(name: 'uniq_api_tokens_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_api_tokens_user', columns: ['user_id'])]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'token_hash', length: 64)]
    private string $tokenHash;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'expires_at', type: 'datetime_immutable')]
    private DateTimeImmutable $expiresAt;

    public function __construct(User $user, string $plainToken, DateTimeImmutable $expiresAt)
    {
        $this->user = $user;
        $this->tokenHash = self::hash($plainToken);
        $this->createdAt = new DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function getId(): int
    {
        return $this->id ?? throw new LogicException('The token has not been persisted yet.');
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(DateTimeImmutable $now = new DateTimeImmutable()): bool
    {
        return $this->expiresAt <= $now;
    }
}
