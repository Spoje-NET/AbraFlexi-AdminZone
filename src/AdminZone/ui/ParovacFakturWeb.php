<?php

namespace AdminZone\ui;

/**
 * Description of EngineWeb
 *
 * @author vitex
 */
class ParovacFakturWeb extends \AdminZone\ParovacFaktur
{
    public $logMessages = [];

    public function __construct($options = array())
    {
        self::unlinkLogfile();
        parent::__construct($options);
    }

    public function addStatusMessage($message, $type = 'info', $addIcons = true)
    {
        $htmlMessage = '<div class="MessageForUser" style="' . $this->logger->logStyles[$type] . '" >' . \Ease\Logger\Message::getTypeUnicodeSymbol($type) . ' ' . $message . '</div>' . "\n";
        $this->logMessages[] = $htmlMessage;
        file_put_contents(
            self::logFile(),
            array_reverse(array_slice($this->logMessages, -10, 10, true))
        );
        parent::addStatusMessage($message, $type, $addIcons);
        echo $htmlMessage;
        flush();
    }

    public static function unlinkLogfile()
    {
        $logFile = self::logFile();
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public static function logFile()
    {
        return '/tmp/status.html';
    }

    public function __destruct()
    {
        self::unlinkLogfile();
    }
}
