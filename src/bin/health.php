<?php

namespace AdminZone;

use AdminZone\Zabbix\Request\Packet as ZabbixPacket;
use AdminZone\Zabbix\Request\Metric as ZabbixMetric;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2024 Spoje.Net
 */

require_once 'includes/Init.php';

$hc = new ui\HealthCehck();

$sender = new Zabbix\ZabbixSender(\Ease\Shared::cfg('ZABBIX_SERVER'));
$packet = new ZabbixPacket();
$me = \Ease\Shared::cfg('ZABBIX_HOST');
$packet->addMetric((new ZabbixMetric('system.apis', json_encode($hc->raw())))->withHostname($me));
$sender->send($packet);
