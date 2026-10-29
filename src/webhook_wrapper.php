<?php

namespace AdminZone;

/**
 * AdminZone - WebHook Acceptor & Saver to file cache.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2022 Spoje.Net
 */

define('EASE_APPNAME', 'IntegromatWrapper');
require_once 'includes/Init.php';

$igmtUrl = \Ease\Shared::cfg('FOREGIN_ENPOINT');

$hooker = new HookReciever();
//$hooker->debug = true;

$apiResponseRaw = $hooker->listen();

$data = ($apiResponseRaw);

$engine = new WebHooker();

function interested($webhookData)
{
    $result = [];
    $destinations = [
        'destination' => 'integromat',
        'filters' => [
            'agenda' => [
                'faktura-vydana' =>
                ['operations' =>
                    [
                        'update' => ['cols' => ['stavuhrk' => 'stavUhr.uhrazeno', 'storno' => 't']],
                    ]
                ]
            ],
        ]
    ];

    $history = new WebHooker();
    $history->setEvidence($webhookData['@evidence']);
    $history->setMyKey(intval($webhookData['id']));

    foreach ($destinations['filters']['agenda'] as $agenda => $agendaRules) {
        if ($webhookData['@evidence'] == $agenda) {
            foreach ($agendaRules['operations'] as $op => $opRules) {
                if ($op == $webhookData['@operation']) {
                    $changes = $history->getChanges();
                    foreach ($changes as $change) {
                        if (array_key_exists($change['sloupec'], $opRules['cols'])) {
                            if ($change['zmenena_na'] == $opRules['cols'][$change['sloupec']]) {
                                $result[$change['sloupec']] = $change['sloupec'] . ':' . $change['zmenena_na'];
                            }
                        }
                    }
                }
            }
        }
    }

    return $result;
}

foreach ($data['winstrom']['changes'] as $id => $record) {
    if ($ch = interested($record)) {
        $data['winstrom']['changes'][$id]['premodified'] = 'OK';
        $engine->setEvidence($record['@evidence']);
        $engine->setMyKey(intval($record['id']));
//    $engine->loadFromAbraFlexi(empty($record['external-ids']) ? (int)$record['id'] : current($record['external-ids']) );
//    $data['winstrom']['changes'][$id]['id'] = $engine->getRecordID();
        $data['winstrom']['changes'][$id]['id'] = $record['id'];
        foreach ($ch as $change) {
            $data['winstrom']['changes'][$id]['external-ids'][] = 'ext:' . ($change);
        }

        $data['winstrom']['changes'][$id]['modified'] = true;
    }
}

if (!empty($data)) {
    $content = json_encode($data);

    $curl = curl_init($igmtUrl);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $curl,
        CURLOPT_HTTPHEADER,
        array("Content-type: application/json")
    );
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

    $json_response = curl_exec($curl);

    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    $engine->addStatusMessage($json_response, $status == 200 ? 'success' : 'error');
}
