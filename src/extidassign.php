<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * AdminZone - pair service account with AbraFlexi address (ajax).
 *
 * @copyright  2018-2026 Spoje.Net
 */

require_once 'includes/Init.php';

header('Content-Type: application/json');

if (!$oUser->isLogged()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => _('Sign in first')]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => _('Bad request')]);

    exit;
}

$service = ExtIdService::create((string) $oPage->getRequestValue('service'));
$serviceId = trim((string) $oPage->getRequestValue('id'));
$code = trim((string) $oPage->getRequestValue('code'));

if ($service === null || $serviceId === '' || $code === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => _('Missing parameters')]);

    exit;
}

$success = $service->assign($serviceId, $code);

echo json_encode(['success' => $success, 'message' => sprintf($success ? _('%s paired with %s') : _('Pairing of %s with %s failed'), $serviceId, $code)]);
