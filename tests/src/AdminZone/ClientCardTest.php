<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\AdminZone;

use AdminZone\ClientCard;
use PHPUnit\Framework\TestCase;

class ClientCardTest extends TestCase
{
    private const ADDRESS = ['id' => '7', 'kod' => 'ACME', 'nazev' => 'Acme s.r.o.', 'ic' => '12345678', 'dic' => 'CZ12345678', 'email' => 'info@acme.example', 'tel' => '+420111222333', 'mobil' => '', 'ulice' => 'Hlavní 1', 'psc' => '11000', 'mesto' => 'Praha', 'stitky' => 'VIP, SLUZBY'];

    private static function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-11-17');
    }

    private static function build(array $parts = []): array
    {
        $parts += ['address' => self::ADDRESS, 'contacts' => [], 'banks' => [], 'open' => [], 'year' => [], 'contracts' => [], 'ext' => []];

        return ClientCard::build($parts['address'], $parts['contacts'], $parts['banks'], $parts['open'], $parts['year'], $parts['contracts'], $parts['ext'], self::today());
    }

    public function testProfile(): void
    {
        $profile = self::build()['profile'];

        $this->assertSame('ACME', $profile['code']);
        $this->assertSame('Hlavní 1, 11000 Praha', $profile['address']);
        $this->assertSame('+420111222333', $profile['phone']);
        $this->assertSame(['VIP', 'SLUZBY'], $profile['labels']);
    }

    public function testContactsAndBankAccounts(): void
    {
        $card = self::build([
            'contacts' => [['jmeno' => 'Jan', 'prijmeni' => 'Novák', 'funkce' => 'ředitel', 'email' => 'jan@acme.example', 'mobil' => '777', 'tel' => '']],
            'banks' => [['buc' => '123456789', 'smerKod' => 'code:0800'], ['buc' => '', 'iban' => 'CZ6508000000192000145399']],
        ]);

        $this->assertSame([['name' => 'Jan Novák', 'role' => 'ředitel', 'email' => 'jan@acme.example', 'phone' => '777']], $card['contacts']);
        $this->assertSame(['123456789/0800', 'CZ6508000000192000145399'], $card['bankAccounts']);
    }

    public function testReceivablesSplitOverdueFromWaiting(): void
    {
        $card = self::build(['open' => [
            ['id' => '1', 'datSplat' => '2026-10-01+02:00', 'zbyvaUhradit' => '1000'],
            ['id' => '2', 'datSplat' => '2026-11-10+01:00', 'zbyvaUhradit' => '500.5'],
            ['id' => '3', 'datSplat' => '2026-12-01+01:00', 'zbyvaUhradit' => '200'],
            ['id' => '4', 'datSplat' => '2026-09-01+02:00', 'zbyvaUhradit' => '0'],
        ]]);
        $receivables = $card['receivables'];

        $this->assertSame(3, $receivables['count']);
        $this->assertEqualsWithDelta(1700.5, $receivables['total'], 0.001);
        $this->assertSame(2, $receivables['overdueCount']);
        $this->assertEqualsWithDelta(1500.5, $receivables['overdueTotal'], 0.001);
        $this->assertSame('2026-10-01', $receivables['oldestDue']);
        $this->assertSame(47, $receivables['maxDaysOverdue']);
        $this->assertSame('danger', $card['signals'][0]['level']);
    }

    public function testTurnoverAndRecentInvoices(): void
    {
        $year = [];

        for ($i = 1; $i <= 12; ++$i) {
            $year[] = ['id' => (string) $i, 'kod' => 'FV'.$i, 'datVyst' => '2026-10-01', 'datSplat' => '2026-10-15', 'sumCelkem' => '100', 'zbyvaUhradit' => $i === 1 ? '100' : '0'];
        }

        $card = self::build(['year' => $year]);

        $this->assertSame(12, $card['invoiceCount']);
        $this->assertEqualsWithDelta(1200.0, $card['turnover'], 0.001);
        $this->assertCount(ClientCard::RECENT_INVOICES, $card['invoices']);
        $this->assertTrue($card['invoices'][0]['overdue']);
        $this->assertFalse($card['invoices'][1]['overdue']);
    }

    public function testContractsActiveEndedAndExpiring(): void
    {
        $card = self::build(['contracts' => [
            ['id' => '1', 'kod' => 'S1', 'smlouvaOd' => '2025-01-01', 'smlouvaDo' => '2026-12-31'],
            ['id' => '2', 'kod' => 'S2', 'smlouvaOd' => '2024-01-01', 'smlouvaDo' => '2025-12-31'],
            ['id' => '3', 'kod' => 'S3', 'smlouvaOd' => '2025-01-01', 'smlouvaDo' => ''],
            ['id' => '4', 'kod' => 'S4', 'smlouvaOd' => '2027-01-01', 'smlouvaDo' => ''],
            ['id' => '5', 'kod' => 'S5', 'smlouvaOd' => '2025-01-01', 'smlouvaDo' => '', 'datVypoz' => '2026-06-01'],
        ]]);

        $this->assertSame(['S1', 'S3'], array_column($card['contracts']['active'], 'code'));
        $this->assertSame(['S2', 'S5'], array_column($card['contracts']['ended'], 'code'));
        $this->assertSame(44, $card['contracts']['active'][0]['expiresInDays']);
        $this->assertNull($card['contracts']['active'][1]['expiresInDays']);
        $this->assertContains('warning', array_column($card['signals'], 'level'));
    }

    public function testSignalsWhenThereIsNoEmail(): void
    {
        $address = ['email' => ''] + self::ADDRESS;

        $this->assertContains('warning', array_column(self::build(['address' => $address])['signals'], 'level'));

        $withContact = self::build(['address' => $address, 'contacts' => [['jmeno' => 'A', 'prijmeni' => 'B', 'email' => 'a@b.example']]]);
        $this->assertSame(['success'], array_column($withContact['signals'], 'level'));
    }

    public function testQuietClientIsGreen(): void
    {
        $this->assertSame([['level' => 'success', 'text' => 'Everything is paid, nothing to take care of.']], self::build()['signals']);
    }

    public function testIntegrations(): void
    {
        $card = self::build(['ext' => ['ipex' => '4711', 'subreg' => null, 'sltv' => '']]);

        $this->assertSame('4711', $card['integrations']['ipex']['id']);
        $this->assertNull($card['integrations']['subreg']['id']);
        $this->assertNull($card['integrations']['sltv']['id']);
        $this->assertSame('SledovaniTV', $card['integrations']['sltv']['title']);
    }

    public function testMoney(): void
    {
        $this->assertSame("1\u{00A0}234,50\u{00A0}Kč", ClientCard::money(1234.5));
    }
}
