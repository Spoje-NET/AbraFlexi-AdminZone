<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * AdminZone - pair IPEX / Subreg / SledovaniTV accounts with AbraFlexi customers (EXT:id).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018-2026 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$service = ExtIdService::create((string) $oPage->getRequestValue('service'));

if ($service === null) {
    $oPage->redirect('extids.php?service=ipex');

    exit;
}

$oPage->addToHeader(new ui\PageTop($service->title()));

$tabs = new \Ease\Html\UlTag(null, ['class' => 'nav nav-tabs mt-4']);

foreach (ExtIdService::$services as $key => $class) {
    $tabs->addItem(new \Ease\Html\LiTag(
        new \Ease\Html\ATag(
            'extids.php?service=' . $key,
            (new $class())->title(),
            ['class' => 'nav-link' . ($key === $service->key() ? ' active' : '')],
        ),
        ['class' => 'nav-item'],
    ));
}

$oPage->container->addItem($tabs);
$oPage->container->addItem(new \Ease\Html\DivTag(null, ['id' => 'extid-status', 'class' => 'alert mt-3', 'style' => 'display:none']));

[$newUrl, $newCaption] = $service->newAccountLink();
$oPage->container->addItem(new \Ease\Html\DivTag([
    new \Ease\Html\ATag($newUrl, '<i class="fa fa-plus"></i> ' . $newCaption, ['class' => 'btn btn-line btn-sm']),
], ['class' => 'my-3']));
$oPage->container->addItem(new ui\ExtIdGrid($service, [
    'id' => (string) $oPage->getRequestValue('customerId'),
    'code' => \AbraFlexi\Functions::uncode((string) $oPage->getRequestValue('extid')),
]));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
