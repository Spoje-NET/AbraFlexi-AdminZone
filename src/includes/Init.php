<?php

/**
 * AdminZone - Init aplikace.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

namespace AdminZone;

require_once __DIR__ . '/../../vendor/autoload.php';
if (!defined('EASE_APPNAME')) {
    define('EASE_APPNAME', 'AdminZone');
}

new \Ease\Locale('cs_CZ', __DIR__ . '/../../i18n', 'adminzone');

try {
    session_start();
} catch (Exception $exc) {
    echo $exc->getTraceAsString();
}


/**
 * Objekt uživatele User nebo Anonym
 * @global \Ease\User
 */
$oUser = \Ease\Shared::user(null, 'AdminZone\User');

\Ease\Shared::init([
    'ABRAFLEXI_URL', 'ABRAFLEXI_COMPANY',
        ], __DIR__ . '/../../.env', true);

// Data calls use the service account when ABRAFLEXI_LOGIN/ABRAFLEXI_PASSWORD are configured
// (the operator's token then only proves who signed in); otherwise they act as the
// signed-in operator through the AbraFlexi authSessionId token.
if (
    !User::hasServiceAccount()
    && method_exists($oUser, 'getAuthSessionId')
    && $oUser->getAuthSessionId()
) {
    putenv('ABRAFLEXI_AUTHSESSID=' . $oUser->getAuthSessionId());
}

$loggers = ['syslog'];

if (PHP_SAPI == 'cli') {
    $loggers[] = 'console';
} else {
    /* @var $oPage \Sys\WebPage */
    $oPage = new ui\WebPage('');
}

define('EASE_LOGGER', implode('|', $loggers));

$engine = new AbraFlexiEngine();

if (PHP_SAPI !== 'cli') {
    // readable error page instead of a blank 500; details only go to the log
    // unless APP_DEBUG is enabled (never expose server URLs to visitors)
    set_exception_handler(static function (\Throwable $exc): void {
        $reference = bin2hex(random_bytes(6));
        error_log(sprintf('AdminZone unhandled [%s] %s: %s in %s:%d', $reference, $exc::class, $exc->getMessage(), $exc->getFile(), $exc->getLine()));

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }

        $debug = filter_var(\Ease\Shared::cfg('APP_DEBUG', false), \FILTER_VALIDATE_BOOLEAN);

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>AdminZone</title>'
            . '<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet"></head>'
            . '<body><div class="container" style="margin-top: 40px"><div class="alert alert-danger"><strong>'
            . htmlspecialchars(_('The request failed')) . '</strong><br>'
            . htmlspecialchars(sprintf(_('Error reference: %s'), $reference))
            . ($debug ? '<br>' . htmlspecialchars($exc->getMessage()) : '')
            . '</div><a class="btn btn-default" href="index.php">' . htmlspecialchars(_('Back')) . '</a> '
            . '<a class="btn btn-default" href="logout.php">' . htmlspecialchars(_('Sign Out')) . '</a></div></body></html>';
    });
}
