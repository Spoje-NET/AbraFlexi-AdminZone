<?php

/**
 * AdminZone - Vršek stránky.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

/**
 * Page TOP.
 */
class PageTop extends \Ease\Html\DivTag
{
    /**
     * Titulek stránky.
     *
     * @var type
     */
    public $pageTitle = 'Page Heading';

    /**
     * Vloží vršek stránky a hlavní menu.
     */
    public function finalize(): void
    {
        $this->addItem(new MainMenu());

        if (get_class(\Ease\Shared::user()) == 'AdminZone\User') {
            $this->addItem(new History());
        }
        parent::finalize();
    }
}
