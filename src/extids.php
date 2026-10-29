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

$oPage->onlyForUser();

$service = ExtIdService::create((string) $oPage->getRequestValue('service'));

if ($service === null) {
    $oPage->redirect('extids.php?service=ipex');

    exit;
}

$oPage->addItem(new ui\PageTop($service->title()));

$tabs = new \Ease\Html\UlTag(null, ['class' => 'nav nav-tabs']);

foreach (ExtIdService::$services as $key => $class) {
    $tabs->addItem(new \Ease\Html\LiTag(
        new \Ease\Html\ATag('extids.php?service='.$key, (new $class())->title()),
        $key === $service->key() ? ['class' => 'active'] : [],
    ));
}

$oPage->container->addItem($tabs);
$oPage->container->addItem(new \Ease\Html\DivTag(null, ['id' => 'extid-status', 'class' => 'alert', 'style' => 'display:none; margin-top: 1em']));

[$newUrl, $newCaption] = $service->newAccountLink();
$oPage->container->addItem(new \Ease\TWB\Panel(
    $service->title(),
    'info',
    new ui\ExtIdGrid($service, [
        'id' => (string) $oPage->getRequestValue('customerId'),
        'code' => \AbraFlexi\Functions::uncode((string) $oPage->getRequestValue('extid')),
    ]),
    new \Ease\TWB\LinkButton($newUrl, $newCaption, 'default'),
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
