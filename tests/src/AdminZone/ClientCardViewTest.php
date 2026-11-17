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
use AdminZone\ui\ClientCardView;
use PHPUnit\Framework\TestCase;

class ClientCardViewTest extends TestCase
{
    private static function html(array $card): string
    {
        ob_start();
        (new ClientCardView($card))->draw();

        return (string) ob_get_clean();
    }

    public function testCardShowsTheClientAndEscapesWhatAbraFlexiHolds(): void
    {
        $card = ClientCard::build(
            ['id' => '7', 'kod' => 'ACME', 'nazev' => '<script>alert(1)</script> s.r.o.', 'email' => 'info@acme.example', 'stitky' => 'VIP'],
            [['jmeno' => 'Jan', 'prijmeni' => '<b>Novák</b>', 'email' => 'jan@acme.example']],
            [],
            [['id' => '1', 'datSplat' => '2026-10-01', 'zbyvaUhradit' => '1000']],
            [['id' => '1', 'kod' => 'FV1', 'datVyst' => '2026-09-15', 'datSplat' => '2026-10-01', 'sumCelkem' => '1000', 'zbyvaUhradit' => '1000']],
            [['id' => '1', 'kod' => 'S1', 'nazev' => 'Internet', 'smlouvaOd' => '2025-01-01', 'smlouvaDo' => '']],
            ['ipex' => '4711', 'subreg' => null, 'sltv' => null],
            new \DateTimeImmutable('2026-11-17'),
        );
        $html = self::html($card);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>Novák', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('table-danger', $html);
        $this->assertStringContainsString('FV1', $html);
        $this->assertStringContainsString('adresar.php?id=7', $html);
        $this->assertStringContainsString('https://b2b.ipex.cz/customer/dashboard/4711', $html);
        $this->assertStringContainsString('extids.php?service=subreg', $html);
        $this->assertStringContainsString('sltvregister.php?id=code:ACME', $html);
    }

    public function testValuesLookingLikeLinksAreEscaped(): void
    {
        $card = ClientCard::build(
            ['id' => '7', 'kod' => 'X', 'nazev' => 'X', 'ic' => '<a href="javascript:alert(1)">1</a>', 'ulice' => '<a onmouseover=alert(2)>', 'email' => 'a@b.example'],
            [],
            [],
            [],
            [],
            [],
            [],
            new \DateTimeImmutable('2026-11-17'),
        );
        $html = self::html($card);

        $this->assertStringNotContainsString('<a href="javascript:', $html);
        $this->assertStringNotContainsString('<a onmouseover', $html);
        $this->assertStringContainsString('&lt;a onmouseover=alert(2)&gt;', $html);
        $this->assertStringContainsString('<a href="mailto:a@b.example">', $html);
    }

    public function testEmptyCardStillRenders(): void
    {
        $card = ClientCard::build(['id' => '7', 'kod' => 'NEW', 'nazev' => 'New', 'email' => 'a@b.example'], [], [], [], [], [], [], new \DateTimeImmutable('2026-11-17'));
        $html = self::html($card);

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('No contacts.', $html);
        $this->assertStringContainsString('No contracts.', $html);
    }
}
