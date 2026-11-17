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

namespace AdminZone\ui;

use AdminZone\CsrfToken;

/**
 * Picture of a pricelist item with the tools to turn it, crop it and replace it.
 *
 * The work is done by productimage.php; the script js/productimage.js drives the buttons.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ProductImageEditor extends \Ease\Html\DivTag
{
    public function __construct(int $itemId, string $endpoint = 'productimage.php')
    {
        $src = $endpoint.'?type=image&id='.$itemId;

        parent::__construct(null, [
            'class' => 'pimg',
            'data-id' => (string) $itemId,
            'data-endpoint' => $endpoint,
            'data-src' => self::e($src),
            'data-csrf' => CsrfToken::get(),
            'data-working' => _('Working…'),
            'data-done' => _('Saved'),
        ]);

        $aspects = ['free' => _('Free'), '1:1' => '1:1', '4:3' => '4:3', '3:2' => '3:2', '16:9' => '16:9'];
        $options = '';

        foreach ($aspects as $value => $caption) {
            $options .= '<option value="'.self::e($value).'">'.self::e($caption).'</option>';
        }

        $this->addItem(
            '<style>'
            .'.pimg-stage{position:relative;display:inline-block;max-width:100%;line-height:0;touch-action:none;cursor:crosshair;user-select:none}'
            .'.pimg-img{max-width:100%;max-height:60vh;display:block;-webkit-user-drag:none}'
            .'.pimg-crop{position:absolute;border:2px dashed #fff;box-shadow:0 0 0 9999px rgba(0,0,0,.5);pointer-events:none}'
            .'.pimg-empty .pimg-stage{display:none}'
            .'.pimg:not(.pimg-empty) .pimg-none{display:none}'
            .'</style>'
            .'<div class="pimg-none text-muted mb-2">'.self::e(_('The item has no picture yet.')).'</div>'
            .'<div class="pimg-stage"><img class="pimg-img" src="'.self::e($src).'" alt="'.self::e(_('Picture of the item')).'">'
            .'<div class="pimg-crop" hidden></div></div>'
            .'<div class="d-flex flex-wrap gap-2 align-items-center mt-2">'
            .'<div class="btn-group" role="group" aria-label="'.self::e(_('Turn')).'">'
            .'<button type="button" class="btn btn-outline-secondary" data-pimg="rotate" data-degrees="-90" title="'.self::e(_('Turn left')).'"><i class="fa fa-rotate-left"></i></button>'
            .'<button type="button" class="btn btn-outline-secondary" data-pimg="rotate" data-degrees="90" title="'.self::e(_('Turn right')).'"><i class="fa fa-rotate-right"></i></button>'
            .'<button type="button" class="btn btn-outline-secondary" data-pimg="rotate" data-degrees="180" title="'.self::e(_('Turn upside down')).'">180°</button>'
            .'</div>'
            .'<div class="input-group" style="width:auto">'
            .'<label class="input-group-text" for="pimg-aspect-'.$itemId.'">'.self::e(_('Crop ratio')).'</label>'
            .'<select class="form-select pimg-aspect" id="pimg-aspect-'.$itemId.'">'.$options.'</select>'
            .'</div>'
            .'<button type="button" class="btn btn-primary" data-pimg="crop" disabled><i class="fa fa-crop"></i> '.self::e(_('Crop')).'</button>'
            .'<button type="button" class="btn btn-outline-secondary" data-pimg="reset">'.self::e(_('Clear selection')).'</button>'
            .'</div>'
            .'<div class="form-text">'.self::e(_('Drag over the picture to select the area to keep, then press Crop.')).'</div>'
            .'<div class="mt-3"><label class="form-label fw-semibold" for="pimg-file-'.$itemId.'">'.self::e(_('Replace the picture')).'</label>'
            .'<input class="form-control pimg-file" type="file" id="pimg-file-'.$itemId.'" accept="image/jpeg,image/png,image/gif,image/webp">'
            .'<div class="form-text">'.self::e(_('JPEG, PNG, GIF or WebP up to 10 MB. The thumbnail for the shop list is made from the picture.')).'</div></div>'
            .'<div class="pimg-status mt-2 small text-muted" role="status"></div>',
        );
    }

    public function finalize(): void
    {
        $this->includeJavaScript('js/productimage.js');
        parent::finalize();
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
