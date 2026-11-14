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

use AdminZone\ui\PageTop;
use PHPUnit\Framework\TestCase;

class PageTopTest extends TestCase
{
    public function testPageTitleFromDataCannotBreakOutOfTheTitleTag(): void
    {
        new PageTop('X</title><script>alert(1)</script>');

        $title = \Ease\WebPage::singleton()->getPageTitle();

        $this->assertStringNotContainsString('<script>', $title);
        $this->assertStringContainsString('&lt;script&gt;', $title);
    }
}
