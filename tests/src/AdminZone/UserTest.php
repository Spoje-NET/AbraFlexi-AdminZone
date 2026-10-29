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

    public function testServerInfoShowsHostAndCompany(): void
    {
        putenv('ABRAFLEXI_URL=https://abra.example.com:5434');
        putenv('ABRAFLEXI_COMPANY=demo_company');

        $user = new User();
        $info = $user->getServerInfo();

        $this->assertSame('abra.example.com:5434', $info['host']);
        $this->assertSame('demo_company', $info['company']);
        $this->assertSame('demo_company', $info['companyName'], 'falls back to the company code');

        $user->setDataValue('companyName', 'Demo s.r.o.');
        $this->assertSame('Demo s.r.o.', $user->getServerInfo()['companyName']);

        putenv('ABRAFLEXI_URL');
        putenv('ABRAFLEXI_COMPANY');
    }

    public function testOperatorAllowList(): void
    {
        putenv('ABRAFLEXI_ALLOWED_USERS=manager, boss');
        $this->assertTrue(User::isOperatorAllowed('manager'));
        $this->assertTrue(User::isOperatorAllowed('boss'));
        $this->assertFalse(User::isOperatorAllowed('stranger'));

        putenv('ABRAFLEXI_ALLOWED_USERS');
        $this->assertTrue(User::isOperatorAllowed('anybody'), 'empty list allows every authenticated user');
    }
}
