<?php

namespace AdminZone;

use AbraFlexi\Bricks\Customer;

/**
 * AdminZone - Vytvoření účtu ve AbraFlexi.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$username = $oPage->getRequestValue('username');
$password = $oPage->getRequestValue('password');

$oPage->addItem(new ui\PageTop(_('Sign In')));

if (empty($username) || empty($password)) {
    $oPage->container->addItem(new \Ease\TWB\Panel(
        _('I already have an customer number'),
        'info',
        new ui\AbraFlexiLoginForm()
    ));
} else {
    $customer = new Customer(['username' => $username, 'password' => $password]);
    $addressID = $customer->getAdresar()->getMyKey();
    if ($addressID) {
    } else {
    }
}

$oPage->addItem(new ui\PageBottom());
$oPage->draw();
