<?php
/**
 * System.Spoje.Net - nastavení testů.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */
define('EASE_APPNAME', 'SystemTest');
if (file_exists('../src/includes/Init.php')) {
    require_once '../src/includes/Init.php'; //Test Run
} else {
    require_once 'src/includes/Init.php'; //Create Test
}

//PHPUnit SQL Extension


$oPage   = AdminZone\ui\WebPage::singleton('test');
$oUser   = new AdminZone\Customer();
$oUser->loadFromAbraFlexi(666);
\Ease\Shared::user($oUser);
$changer = new \AbraFlexi\Changes();
$changer->enable();
