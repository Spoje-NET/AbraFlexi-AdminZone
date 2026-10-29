<?php

namespace AdminZone;

/**
 * AdminZone - Stránka firmy/adresy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->onlyForUser();

$address_id = $oPage->getRequestValue('adrid');
if (!strstr($address_id, 'code:')) {
    $address_id = intval($address_id);
}

$address = new \AbraFlexi\Adresar(
    $address_id,
    ['defaultUrlParams' => ['relations' => 'kontakt']]
);

$subregContactId = $address->getExternalID('subreg');

$subreger = new SubregAPI();

$userListRaw = $subreger->call('Contacts_List');
$found = false;
if (array_key_exists('count', $userListRaw)  && intval($userListRaw['count'])) {
    foreach ($userListRaw['contacts'] as $subUserInfo) {
        if ($subUserInfo['org'] == $address->getDataValue('nazev')) {
            $subregContactId = $subUserInfo['id'];
            $found = true;
        }
    }
}


if ($found === false) {
    $abraFlexiInfo = $address->getData();

    $subregContactData['name'] = explode(' ', $abraFlexiInfo['nazev'])[1];
    $subregContactData['surname'] = explode(' ', $abraFlexiInfo['nazev'])[0];
    $subregContactData['org'] = $abraFlexiInfo['nazev'];
    $subregContactData['street'] = $abraFlexiInfo['ulice'];
    $subregContactData['city'] = $abraFlexiInfo['mesto'];
    $subregContactData['pc'] = $abraFlexiInfo['psc'];
    $subregContactData['sp'] = $abraFlexiInfo['stat']->showAs;
    $subregContactData['cc'] = \AbraFlexi\Functions::uncode($abraFlexiInfo['stat']);
    $subregContactData['phone'] = $abraFlexiInfo['tel'];
    $subregContactData['fax'] = $abraFlexiInfo['fax'];
    $subregContactData['email'] = $abraFlexiInfo['email'];
//$subregContactData['params']['regid'] = $abraFlexiInfo['']; //NIC ID
//$subregContactData['params']['notify_email'] = $abraFlexiInfo[''];
    $subregContactData['params']['vat'] = $abraFlexiInfo['dic'];
//$subregContactData['params']['ident_type'] = $abraFlexiInfo[''];
//$subregContactData['params']['ident_number'] = $abraFlexiInfo[''];
//$subregContactData['params']['disclose'] = $abraFlexiInfo[''];
    $subregContactIdRaw = $subreger->call(
        'Create_Contact',
        ['contact' => $subregContactData]
    );
    if ($subreger->lastStatus == 'ok') {
        $address->addExternalID('ext:subreg:' . $subregContactIdRaw['contactid']);
    }
}



$oPage->addItem(new ui\PageTop(_('Address to Subreg importer') . ': ' . $address->getDataValue('nazev')));

//$oPage->container->addItem('<pre>'.print_r($result, 1).'</pre>');


$oPage->container->addItem('<pre>' . print_r($userListRaw, 1) . '</pre>');

$oPage->container->addItem(new \Ease\TWB\LinkButton(
    'adresar.php?id=' . $address_id,
    _('Customer Address page'),
    'warning'
));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
