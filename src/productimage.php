<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdminZone;

// An image endpoint must never emit HTML (warnings would break <img> and the JSON answers)
ini_set('display_errors', '0');
ini_set('html_errors', '0');

require_once 'includes/Init.php';

/**
 * Picture of a pricelist item: GET gives it, POST uploads, turns or crops it (answers JSON).
 */
$answer = static function (int $status, array $data): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, \JSON_UNESCAPED_UNICODE);

    exit;
};

if (!$oUser->isLogged()) {
    $answer(403, ['ok' => false, 'error' => _('Sign in first')]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $type = (string) ($_GET['type'] ?? ProductImages::IMAGE);
    $itemId = (int) ($_GET['id'] ?? 0);

    if ($itemId <= 0 || !ProductImages::isType($type)) {
        $answer(400, ['ok' => false, 'error' => _('Wrong request')]);
    }

    try {
        $picture = ProductImages::get($itemId, $type);
    } catch (\Throwable $exc) {
        error_log('AdminZone product picture read failed: '.$exc->getMessage());
        $answer(502, ['ok' => false, 'error' => _('The picture could not be read from AbraFlexi')]);
    }

    if ($picture === null) {
        $answer(404, ['ok' => false, 'error' => _('The item has no picture')]);
    }

    header('Content-Type: '.$picture['mime']);
    header('Content-Length: '.\strlen($picture['body']));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-cache');
    echo $picture['body'];

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $answer(405, ['ok' => false, 'error' => _('Wrong request')]);
}

if (!CsrfToken::isValid($_POST[CsrfToken::FIELD] ?? null)) {
    $answer(403, ['ok' => false, 'error' => _('The form expired, reload the page')]);
}

$itemId = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');

if ($itemId <= 0) {
    $answer(400, ['ok' => false, 'error' => _('Wrong request')]);
}

try {
    if ($action === 'upload') {
        $file = $_FILES['image'] ?? null;

        if (!\is_array($file) || ($file['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            $answer(400, ['ok' => false, 'error' => _('No picture was uploaded')]);
        }

        if ((int) $file['size'] > 10 * 1024 * 1024) {
            $answer(413, ['ok' => false, 'error' => _('The picture is bigger than 10 MB')]);
        }

        $body = (string) file_get_contents((string) $file['tmp_name']);

        if (ImageEditor::mimeOf($body) === null) {
            $answer(415, ['ok' => false, 'error' => _('Only JPEG, PNG, GIF and WebP pictures are accepted')]);
        }
    } else {
        $current = ProductImages::get($itemId, ProductImages::IMAGE);

        if ($current === null) {
            $answer(404, ['ok' => false, 'error' => _('The item has no picture')]);
        }

        $body = match ($action) {
            'rotate' => ImageEditor::rotate($current['body'], (int) ($_POST['degrees'] ?? 0)),
            'crop' => ImageEditor::crop(
                $current['body'],
                (float) ($_POST['x'] ?? -1),
                (float) ($_POST['y'] ?? -1),
                (float) ($_POST['width'] ?? 0),
                (float) ($_POST['height'] ?? 0),
            ),
            default => null,
        };

        if ($body === null) {
            $answer(400, ['ok' => false, 'error' => _('Wrong request')]);
        }
    }

    if (!ProductImages::storeWithThumbnail($itemId, $body)) {
        $answer(502, ['ok' => false, 'error' => _('AbraFlexi did not accept the picture')]);
    }

    $size = ImageEditor::size($body);
    $answer(200, ['ok' => true, 'width' => $size[0] ?? 0, 'height' => $size[1] ?? 0]);
} catch (\InvalidArgumentException $exc) {
    $answer(422, ['ok' => false, 'error' => $exc->getMessage()]);
} catch (\Throwable $exc) {
    error_log('AdminZone product picture failed: '.$exc->getMessage());
    $answer(502, ['ok' => false, 'error' => _('The picture could not be saved to AbraFlexi')]);
}
