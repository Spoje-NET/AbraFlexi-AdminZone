<?php

declare(strict_types=1);

namespace Test\AdminZone;

use AdminZone\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AdminZone\User
 */
class UserTest extends TestCase
{
    public function testEmptyCredentialsAreRefusedWithoutAskingAbraFlexi(): void
    {
        $user = new User();

        $this->assertFalse($user->tryToLogin(['login' => '', 'password' => '']));
        $this->assertNotSame('', $user->lastError);
        $this->assertFalse($user->isLogged());
        $this->assertNull($user->getAuthSessionId());
    }
}
