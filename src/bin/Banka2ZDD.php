<?php

namespace AdminZone;

define('EASE_LOGGER', 'console|syslog');

$inc = 'includes/Init.php';
if (!file_exists($inc)) {
    chdir('..');
}
require_once $inc;

$payment = new \AbraFlexi\Banka('code:B+00007062/19');
$extraValues = ['firma' => 'code:VITEX'];

$copyer = new \AbraFlexi\Bricks\Convertor(
    $payment,
    new \AbraFlexi\FakturaVydana(array_merge(
        $extraValues,
        ['typDokl' => 'code:ZDD',
                            'nazev' => 'TEST ZDD',
                            'stitky' => 'SYSTEM',
                            'stavMailK' => 'stavMail.neodesilat'
            ]
    ))
);

/**
 * @var FakturaVydana ZDD
 */
$zdd = $copyer->conversion();
$zdd->defaultUrlParams['relations'] = 'vazby';

if ($zdd->sync()) {
    $zdd->addStatusMessage(sprintf(
        _('New advance tax document was created: %s '),
        \AbraFlexi\Functions::uncode($zdd)
    ), 'success');
}
