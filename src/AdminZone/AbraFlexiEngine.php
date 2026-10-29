<?php

namespace AdminZone;

/**
 * Description of Engine
 *
 * @author vitex
 */
class AbraFlexiEngine extends \Ease\Sand
{
    /**
     * Configuration
     * @var array
     */
    public $configuration = [];

    /**
     * Where do i get configuration ?
     * @var string
     */
    public $configFile = './config.json';
    private $apiurl;

    /**
     * KreditFrom worker
     *
     * @param array $options Connection settings override
     */
    public function __construct($options = [])
    {
//        if (isset($options['config'])) {
//            $this->configFile = $options['config'];
//        } else {
//            if (file_exists('/etc/system.spoje.net/config.json')) {
//                $this->configFile = '/etc/system.spoje.net/config.json';
//            }
//        }
//        $this->loadConfig($this->configFile);
    }

    /**
     * Load Configuration values from json file $this->configFile and define UPPERCASE keys
     */
    public function loadConfig($configFile)
    {
        \Ease\Shared::instanced()->loadConfig($this->configFile, true);
        $this->configuration = \Ease\Shared::instanced()->configuration;
        $this->apiurl = \Ease\Shared::cfg('ABRAFLEXI_URL');
    }

    /**
     * URL To AdminZone
     * @param \AbraFlexi\RO $document
     * @return string URL
     */
    public static function systemUrl($document)
    {
        if (php_sapi_name() == 'cli') {
            $url = defined('SYSTEM_URL') ? constant('SYSTEM_URL') : 'https://adminzone.example.com/';
        } else {
            $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname(\Ease\WebPage::getUri());
        }
        return $url . 'document.php?id=' . $document->getRecordCode() . '&embed=true&evidence=' . $document->getEvidence();
    }
}
