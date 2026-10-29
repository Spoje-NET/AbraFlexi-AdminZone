<?php

namespace AdminZone;

/**
 * AdminZone - předloha stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

require_once 'includes/Init.php';

//$oPage->onlyForAdmin();

$oPage->addItem(new ui\PageTop(_('AbraFlexi')));

$oPage->container->addItem(new AbraFlexiStatus());

/* Povolit ChangesAPI */

$changer = new \AbraFlexi\Changes();
if ($changer->getStatus() == false) {
    $chEnabled = $changer->enable();
}
if ($changer->getStatus()) {
    $changer->addStatusMessage(_('ChangesApi Povoleno'));
    $oPage->container->addItem(new \Ease\TWB\Label(
        'success',
        _('ChangesAPI povoleno')
    ));
} else {
    $oPage->container->addItem(new \Ease\TWB\Label(
        'warning',
        _('ChangesAPI zakázáno')
    ));
}

/* Zaregistrovat WebHook */
$hooker = new \AbraFlexi\Hooks();
$webhook = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname(\Ease\WebPage::getUri()) . '/webhook.php';
$hookResult = $hooker->register($webhook);

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    $webhook,
    $webhook,
    $hookResult ? 'success' : 'danger'
));

$changer = new HookReciever();

$changesPanel = new \Ease\TWB\Panel(
    sprintf(
        _('Last processed change %d, in AbraFlexi %d'),
        $changer->lastProcessedVersion,
        $changer->getGlobalVersion()
    ),
    'info',
    sprintf(_('Last cached change: %s'), $changer->getLastCachedVersion())
);

$oPage->container->addItem($changesPanel);

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
