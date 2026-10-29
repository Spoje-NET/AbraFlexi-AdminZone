<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of ScriptsLogger
 *
 * @author vitex
 */
class ScriptsLogger extends \Ease\Sand
{
    /**
     *
     * @var type
     */
    static $logDir = '../status/';

    /**
     *
     * @return boolean
     */
    public static function logStart()
    {
        return self::saveLogFile('start');
    }

    /**
     *
     * @return boolean
     */
    public static function logDone()
    {
        return self::saveLogFile('done');
    }

    /**
     *
     * @return string
     */
    static function getSubject()
    {
        return pathinfo($_SERVER['SCRIPT_FILENAME'], PATHINFO_FILENAME);
    }

    /**
     *
     * @return string
     */
    static function getLogFilename()
    {
        return self::$logDir . self::getSubject();
    }

    /**
     *
     * @param string $status
     * @return boolean
     */
    public static function saveLogFile($status)
    {
        return file_put_contents(self::getLogFilename(), time() . "\n" . $status);
    }
}
