<?php

namespace Tests\Entities;

use App\Entities\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testNormalizesEmailAndName(): void
    {
        $user = new User('  Ada@Example.COM ', '  Ada Lovelace ', 'secret password');

        $this->assertSame('ada@example.com', $user->getEmail());
        $this->assertSame('Ada Lovelace', $user->getName());
    }

    public function testPasswordIsHashedAndVerifiable(): void
    {
        $user = new User('ada@example.com', 'Ada', 'secret password');

        $this->assertTrue($user->verifyPassword('secret password'));
        $this->assertFalse($user->verifyPassword('wrong'));

        $user->changePassword('new password');
        $this->assertTrue($user->verifyPassword('new password'));
        $this->assertFalse($user->verifyPassword('secret password'));
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User('not-an-email', 'Ada', 'secret password');
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User('ada@example.com', '   ', 'secret password');
    }

    public function testRejectsEmptyPassword(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new User('ada@example.com', 'Ada', '');
    }

    public function testChangesUpdateTheTimestamp(): void
    {
        $user = new User('ada@example.com', 'Ada', 'secret password');
        $before = $user->getUpdatedAt();
        usleep(1000);

        $user->rename('Countess');

        $this->assertGreaterThan($before, $user->getUpdatedAt());
    }
}
