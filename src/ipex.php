<?php

namespace AdminZone;

/**
 * AdminZone - moved to extids.php
 *
 * @copyright  2018-2026 Spoje.Net
 */

require_once 'includes/Init.php';

$oPage->redirect('extids.php?'.http_build_query(array_filter([
    'service' => 'ipex',
    'customerId' => $oPage->getRequestValue('customerId'),
    'extid' => $oPage->getRequestValue('extid'),
])));
