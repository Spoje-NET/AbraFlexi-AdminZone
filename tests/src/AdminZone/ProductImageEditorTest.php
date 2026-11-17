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

use AdminZone\ProductImages;
use AdminZone\ui\ProductImageEditor;
use PHPUnit\Framework\TestCase;

class ProductImageEditorTest extends TestCase
{
    public function testEditorCarriesTheToolsAndTheToken(): void
    {
        $_SESSION = [];
        ob_start();
        (new ProductImageEditor(42))->draw();
        $html = (string) ob_get_clean();

        $this->assertStringContainsString('data-id="42"', $html);
        $this->assertStringContainsString('data-src="productimage.php?type=image&amp;id=42"', $html);
        $this->assertStringContainsString('data-csrf="'.$_SESSION['csrf_token'].'"', $html);
        $this->assertStringContainsString('data-degrees="-90"', $html);
        $this->assertStringContainsString('data-degrees="90"', $html);
        $this->assertStringContainsString('data-pimg="crop"', $html);
        $this->assertStringContainsString('type="file"', $html);
    }

    public function testKnownTypes(): void
    {
        $this->assertTrue(ProductImages::isType('image'));
        $this->assertTrue(ProductImages::isType('thumbnail'));
        $this->assertFalse(ProductImages::isType('../etc/passwd'));
        $this->assertFalse(ProductImages::isType(''));
    }
}
