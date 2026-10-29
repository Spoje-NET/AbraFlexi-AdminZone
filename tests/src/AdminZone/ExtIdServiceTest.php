<?php

declare(strict_types=1);

namespace Test\AdminZone;

use AdminZone\ExtIdService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AdminZone\ExtIdService
 */
class ExtIdServiceTest extends TestCase
{
    private function service(): ExtIdService
    {
        return new class () extends ExtIdService {
            public function key(): string
            {
                return 'demo';
            }

            public function title(): string
            {
                return 'Demo';
            }

            public function columns(): array
            {
                return ['id' => 'ID', 'customerName' => 'Name'];
            }

            public function newAccountLink(): array
            {
                return ['https://example.com', 'New'];
            }

            protected function accounts(): array
            {
                return [
                    ['id' => 7, 'customerName' => 'Acme', 'login' => 'acme', 'password' => 'secret', '_url' => 'https://example.com/7'],
                    ['id' => 8, 'customerName' => 'Beta', 'password' => 'secret2'],
                ];
            }

            protected function assignedAddresses(): ?array
            {
                return ['7' => ['kod' => 'ACME', 'nazev' => 'Acme s.r.o.']];
            }
        };
    }

    public function testItemsNeverExposeCredentialsOfTheService(): void
    {
        foreach ($this->service()->items() as $row) {
            $this->assertArrayNotHasKey('password', $row);
            $this->assertArrayNotHasKey('login', $row);
        }
    }

    public function testItemsCarryAssignedAddress(): void
    {
        $rows = $this->service()->items();

        $this->assertSame('7', $rows[0]['id']);
        $this->assertSame('ACME', $rows[0]['_code']);
        $this->assertSame('ACME — Acme s.r.o.', $rows[0]['_label']);
        $this->assertSame('', $rows[1]['_code']);
    }

    public function testFactoryKnowsAllServices(): void
    {
        foreach (['ipex', 'subreg', 'sltv'] as $key) {
            $this->assertNotNull(ExtIdService::create($key), $key);
            $this->assertSame($key, ExtIdService::create($key)->key());
        }

        $this->assertNull(ExtIdService::create('unknown'));
    }
}
