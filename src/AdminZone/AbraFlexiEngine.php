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

}
