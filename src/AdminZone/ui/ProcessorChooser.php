<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of ProcessorChooser
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ProcessorChooser extends \Ease\Html\SelectTag {

    public function __construct($name, $default) {
        parent::__construct($name, $this->pluginsToSelect(), $default);
    }

    /**
     *
     * @return array
     */
    public function pluginsToSelect() {
        $plugins = ['' => _('None')];
        foreach (\AdminZone\OrderItem::findPlugins() as $pluginKeyWord => $plugin) {
            $plugins[$pluginKeyWord] = $plugin->name;
        }
        return $plugins;
    }
}
