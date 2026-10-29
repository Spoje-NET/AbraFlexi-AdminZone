<?php

namespace AdminZone;

/**
 * AdminZone - Hlavní strana.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2024 Spoje.Net
 */

exit;

header('Content-Type: application/json');
require_once 'includes/Init.php';

$hc = new ui\HealthCehck();

echo json_encode($hc->raw());
