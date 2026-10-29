<?php

namespace AdminZone\ui;

/**
 * Description of EngineWeb
 *
 * @author vitex
 */
class EngineWeb extends \AdminZone\AbraFlexiEngine
{
    public $logMessages = [];

    public function __construct($options = array())
    {
        self::unlinkLogfile();
        $_SESSION['messages'] = [];
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

//    /**
//     * Import all files in ./xslt directory
//     */
//    public function updateXSLTs($dir = '../src/xslt/')
//    {
//        parent::updateXSLTs($dir);
//    }

    public static function unlinkLogfile()
    {
        $logFile = self::logFile();
        if (file_exists($logFile)) {
            unlink($logFile);
        }
    }

    public static function logFile()
    {
        return '../tmp/status.html';
    }

    public function __destruct()
    {
        $_SESSION['messages'] = [];
        self::unlinkLogfile();
        parent::__destruct();
    }
}
