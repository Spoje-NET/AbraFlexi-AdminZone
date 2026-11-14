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
$oPage->addToHeader(new ui\PageTop(_('Pricelist check')));

$oPage->container->addItem(new \Ease\Html\H1Tag(_('Pricelist check'), ['class' => 'mt-4']));
$oPage->container->addItem(new \Ease\Html\PTag(
    _('Checks that the pricelist items are entered so that ClientZone can sell them.'),
    ['class' => 'dim'],
));

$severity = (string) $oPage->getRequestValue('severity');
$severity = \in_array($severity, PricelistFinding::SEVERITIES, true) ? $severity : null;

try {
    $items = PricelistChecker::fetch();
    $expected = array_filter(array_map('trim', explode(',', (string) \Ease\Shared::cfg('PRICELIST_EXPECTED_PRODUCTS', ''))));
    $findings = (new PricelistChecker($expected))->check($items);
    $oPage->container->addItem(new ui\PricelistReport(
        $items,
        $findings,
        $severity,
        \Ease\Shared::cfg('ABRAFLEXI_URL').'/c/'.\Ease\Shared::cfg('ABRAFLEXI_COMPANY').'/cenik/',
    ));
} catch (\AbraFlexi\Exception $exc) {
    error_log('AdminZone pricelist check failed: '.$exc->getMessage());
    $oPage->container->addItem(new \Ease\Html\DivTag(_('The pricelist could not be read from AbraFlexi.'), ['class' => 'alert alert-danger mt-3']));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
