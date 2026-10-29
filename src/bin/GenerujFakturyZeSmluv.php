<?php

namespace AdminZone;

/**
 * Generování faktur
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018-2024 Spoje.Net
 */

define('EASE_APPNAME', 'GenerujFakturyZeSmluv');
define('EASE_LOGGER', 'syslog|console|email');

exit();
// Disabled https://crm.spoje.net/issues/18070

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$invoicer = new \AbraFlexi\FakturaVydana();
$contractor = new \AbraFlexi\Smlouva(null, ['debug' => false]);
$contractor->logBanner(\Ease\Shared::appName());
$contractList = $contractor->getColumnsFromAbraFlexi(['id', 'kod', 'nazev', 'firma'], ['autoGen' => true, 'limit' => 0]);
foreach ($contractList as $counter => $contractInfo) {
    $message = $counter . '/' . count($contractList) . ' ' . $contractInfo['nazev'] . ' ' . $contractInfo['firma']->showAs;

    $contractor->setMyKey($contractInfo['id']);
    try {
        if ($contractor->generovaniFaktur()) {
            if (array_key_exists('messages', $contractor->lastResult)) {
                if (strstr($contractor->lastResult['messages']['message'], 'Nebyla')) {
                    $contractor->addStatusMessage($message, 'debug');
                } else {
                    if (
                        strstr(
                            $contractor->lastResult['messages']['message'],
                            'faktur'
                        ) && strstr(
                            $contractor->lastResult['messages']['message'],
                            ':'
                        )
                    ) {
                        $hmr = explode(
                            ':',
                            $contractor->lastResult['messages']['message']
                        );
                        $howMany = intval(end($hmr));
                        $generated = $invoicer->getColumnsFromAbraFlexi(
                            ['kod'],
                            [
                                    'firma' => \AbraFlexi\Functions::code($contractInfo['firma']),
                                    'cisSml' => $contractInfo['kod'],
                                    'limit' => $howMany
                            ]
                        );

                        $invoices = [];
                        foreach ($generated as $result) {
                            $invoices[] = $result['kod'];
                        }

                        $contractor->addStatusMessage($message . ' ' . implode(
                            ',',
                            $invoices
                        ), 'success');
                    }
                }
            } else {
                if (array_key_exists('success', $contractor->lastResult) && ($contractor->lastResult['success'] == 'failed')) {
                    $contractor->addStatusMessage($message . ' ' . $notice, 'warning');
                }
            }
        } else {
            $status = $contractor->lastResponseCode == 500 ? 'error' : 'warning';
            $notice = str_replace(
                '"',
                ' ',
                trim(json_encode($contractor->getErrors()), '[{}]')
            );
            $contractor->addStatusMessage($message . ' ' . $notice, $status);
        }
    } catch (\AbraFlexi\Exception $exc) {
        $contractor->addStatusMessage($exc->getTraceAsString(), 'error');
    }
}
