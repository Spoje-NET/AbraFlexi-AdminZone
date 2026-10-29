<?php

namespace AdminZone;

/**
 * AdminZone - CleanUP emails with ,.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

define('EASE_APPNAME', 'EamailsFixer');
define('EASE_LOGGER', 'syslog|email|console');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$checker = new \AbraFlexi\Adresar();
$candidates = $checker->getAllFromAbraFlexi(['detail' => 'full']);
$kontakter = new \AbraFlexi\Kontakt();

$cleaned = [];

foreach ($candidates as $code => $rowData) {
    $mail = $rowData['email'];
    if (strstr($mail, ',')) {
        $mails = \AbraFlexi\Stitek::listToArray($mail);
        if (!empty($mails)) {
            $firstmail = current($mails);
            $checker->insertToAbraFlexi(['id' => $rowData['id'], 'email' => $firstmail]);
            $checker->addStatusMessage(
                'Addr: ' . $rowData['kod'] . ' ' . $rowData['nazev'] . ' ' . $mail,
                ($checker->lastResponseCode == 201) ? 'success' : 'warning'
            );
            array_shift($mails);
            if (!empty($mails)) {
                $currentEmails = $kontakter->getColumnsFromAbraFlexi(
                    ['email', 'kod'],
                    ['firma' => \AbraFlexi\Functions::code($rowData['kod'])],
                    'email'
                );

                foreach ($currentEmails as $currentEmail => $currentEmailInfo) {
                    if (strstr($currentEmail, ',')) {
                        $cmails = \AbraFlexi\Stitek::listToArray($currentEmail);
                        if (!empty($cmails)) {
                            $kontakter->addStatusMessage(
                                'Splitting: ' . $currentEmail,
                                'warning'
                            );
                            $kontakter->deleteFromAbraFlexi($currentEmailInfo['id']);
                            foreach ($cmails as $cmail) {
                                $kontakter->insertToAbraFlexi(['id' => $rowData['id'],
                                    'email' => $cmail, 'firma' => \AbraFlexi\Functions::code($rowData['kod'])]);
                                $kontakter->addStatusMessage($cmail, $kontakter->lastResponseCode == 201 ? 'success' : 'error');
                            }
                        }
                    }
                }

                $currentEmails = $kontakter->getColumnsFromAbraFlexi(
                    ['email', 'kod'],
                    ['firma' => \AbraFlexi\Functions::code($rowData['kod'])],
                    'email'
                );

                foreach ($mails as $mail) {
                    if (!array_key_exists($mail, $currentEmails)) {
                        $kontakter->insertToAbraFlexi(['mail' => $mail, 'popis' => 'odděleno z adresy',
                            'firma' => \AbraFlexi\Functions::code($rowData['kod'])]);
                        $kontakter->addStatusMessage(
                            'Contact: ' . $rowData['kod'] . ' ' . $rowData['nazev'] . ' ' . $mail,
                            ($kontakter->lastResponseCode == 201) ? 'success' : 'warning'
                        );
                    }
                }
            }
        }
    }
}
