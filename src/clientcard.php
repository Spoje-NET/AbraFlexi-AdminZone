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

// The client is given by the address id or by its code ("code:ACME"), as adresar.php does
$requested = trim((string) $oPage->getRequestValue('id'));
$card = null;
$failed = false;

try {
    if ($requested !== '') {
        $code = $requested;

        if (!str_starts_with($code, 'code:')) {
            $address = new \AbraFlexi\Adresar((int) $code, ['nativeTypes' => false]);
            $code = (string) $address->getDataValue('kod');
        } else {
            $code = substr($code, 5);
        }

        $card = $code !== '' ? ClientCard::fetch($code) : null;
    }
} catch (\Throwable $exc) {
    error_log('AdminZone client card failed: '.$exc->getMessage());
    $failed = true;
}

if ($card === null) {
    http_response_code($failed ? 502 : 404);
    $oPage->addToHeader(new ui\PageTop(_('Client card')));
    $oPage->container->addItem(new \Ease\Html\DivTag(
        $failed ? _('The client could not be read from AbraFlexi.') : _('The client was not found.'),
        ['class' => 'alert alert-'.($failed ? 'danger' : 'warning').' mt-4'],
    ));
    $oPage->container->addItem(new \Ease\Html\ATag('clients.php', _('Back to the clients'), ['class' => 'btn btn-outline-secondary']));
} else {
    $oPage->addToHeader(new ui\PageTop(_('Client card').': '.$card['profile']['name']));
    $oPage->container->addItem(new ui\ClientCardView($card));
}

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
