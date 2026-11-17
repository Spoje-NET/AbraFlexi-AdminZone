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

namespace AdminZone;

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$itemId = (int) $oPage->getRequestValue('id') ?: null;
$errors = [];

try {
    $tree = PricelistTree::fetch();
    $choices = PricelistItem::fetchChoices();
    $nodeIds = array_column($tree->flat(), 'id');
    $catalog = PricelistTree::refId(\Ease\Shared::cfg('PROD_CAT', '1'));
    $catalog = $catalog !== null && $tree->has($catalog) ? $catalog : null;

    $cenik = new \AbraFlexi\Cenik($itemId, ['nativeTypes' => false]);

    if ($itemId !== null && (int) $cenik->getMyKey() !== $itemId) {
        http_response_code(404);
        $oPage->addToHeader(new ui\PageTop(_('Pricelist')));
        $oPage->container->addItem(new \Ease\Html\DivTag(_('The pricelist item was not found.'), ['class' => 'alert alert-warning mt-4']));
        $oPage->addItem(new ui\PageBottom());
        $oPage->draw();

        exit;
    }

    $values = $itemId !== null ? $cenik->getData() : ['typZasobyK' => 'typZasoby.sluzba', 'typSzbDphK' => 'typSzbDph.dphZakl', 'platiOd' => '0', 'platiDo' => '9999', 'cenaZakl' => '0'];
    $node = $itemId !== null ? ($tree->nodeIdsOfItem($itemId)[0] ?? null) : PricelistTree::refId($oPage->getRequestValue('node'));

    if ($oPage->isPosted()) {
        if (!CsrfToken::isValid($_POST[CsrfToken::FIELD] ?? null)) {
            http_response_code(403);
            $errors[] = _('The form expired, please send it again');
        } else {
            $normalized = PricelistItem::normalize($_POST, $choices, array_map('intval', $nodeIds));
            $errors = $normalized['errors'];
            // Show what was posted when it cannot be stored
            $values = array_merge($values, $normalized['data'], ['stitky' => $normalized['data']['stitky']]);
            $node = $normalized['node'];

            if ($errors === []) {
                $saved = PricelistItem::save($normalized['data'], $itemId, $node);

                if ($saved !== null) {
                    header('Location: pricelistitem.php?id='.$saved.'&saved=1');

                    exit;
                }

                error_log('AdminZone pricelist item save failed: '.implode('; ', array_map(static fn ($message): string => json_encode($message, \JSON_UNESCAPED_UNICODE) ?: '', $cenik->getStatusMessages())));
                $errors[] = _('AbraFlexi did not accept the item, it was not saved');
            }
        }
    }

    $label = $itemId !== null ? trim((string) ($values['kod'] ?? '')).' - '.trim((string) ($values['nazev'] ?? '')) : _('New pricelist item');
    $oPage->addToHeader(new ui\PageTop($label));

    $head = new \Ease\Html\DivTag(
        new \Ease\Html\H1Tag(htmlspecialchars($label), ['class' => 'h3']),
        ['class' => 'd-flex justify-content-between align-items-center mt-4 mb-3'],
    );

    if ($itemId !== null) {
        $head->addItem(new ui\LinkToAbraFlexiButton($cenik));
    }

    $oPage->container->addItem($head);

    if ($oPage->getRequestValue('saved')) {
        $oPage->container->addItem(new \Ease\Html\DivTag(_('The item was saved'), ['class' => 'alert alert-success']));
    }

    foreach ($errors as $error) {
        $oPage->container->addItem(new \Ease\Html\DivTag(htmlspecialchars($error), ['class' => 'alert alert-danger']));
    }

    $status = '';

    foreach (PricelistItem::visibility(array_merge($values, ['id' => $itemId ?? 0]), $tree, $catalog) as $line) {
        $status .= '<li class="'.($line['ok'] ? 'text-success' : 'text-danger').'"><i class="fa '.($line['ok'] ? 'fa-circle-check' : 'fa-circle-xmark').'"></i> '.htmlspecialchars($line['text']).'</li>';
    }

    $row = new \Ease\Html\DivTag(null, ['class' => 'row g-3']);
    $row->addItem(new \Ease\Html\DivTag(
        new \Ease\TWB5\Panel(_('Item'), 'info', new ui\PricelistItemForm($values, $choices, $tree, $node, $itemId)),
        ['class' => 'col-lg-9'],
    ));
    $side = new \Ease\Html\DivTag(
        new \Ease\TWB5\Panel(_('What customers see'), 'info', '<ul class="list-unstyled mb-2">'.$status.'</ul>'
            .'<div class="form-text">'.htmlspecialchars(_('As saved in AbraFlexi: the labels, the placement and the validity take effect after saving.')).'</div>'),
        ['class' => 'col-lg-3'],
    );

    // A picture is an attachment of a stored item, so a new item gets it after the first save
    if ($itemId !== null) {
        $side->addItem(new \Ease\Html\DivTag(
            new \Ease\TWB5\Panel(_('Picture'), 'info', new ui\ProductImageEditor($itemId)),
            ['class' => 'mt-3'],
        ));
    }

    $row->addItem($side);
    $oPage->container->addItem($row);
} catch (\AbraFlexi\Exception $exc) {
    error_log('AdminZone pricelist item failed: '.$exc->getMessage());
    $oPage->addToHeader(new ui\PageTop(_('Pricelist')));
    $oPage->container->addItem(new \Ease\Html\DivTag(_('The pricelist could not be read from AbraFlexi.'), ['class' => 'alert alert-danger mt-3']));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
