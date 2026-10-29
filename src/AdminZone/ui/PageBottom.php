<?php

/**
 * AdminZone - Spodek Stránky Webu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

namespace AdminZone\ui;

class PageBottom extends \Ease\Html\FooterTag
{
    public function __construct($content = null)
    {
        parent::__construct($content);
        $this->SetTagID('footer');
        $this->addItem('<hr>');

        $rowFluid2 = new \Ease\TWB\Row();

        if (method_exists('Composer\InstalledVersions', 'getRootPackage')) {
            $composer = \Composer\InstalledVersions::getRootPackage()['install_path'] . '/composer.lock';
        } else {
            $composer = '../composer.lock';
        }

        $rowFluid2->addItem(new \Ease\TWB\Col(
            12,
            [new \Ease\TWB\Col(8, ''), new \Ease\TWB\Col(
                4,
                'abraflexi-adminzone ' . _('Version') . ': ' . \Ease\Shared::appVersion() . ' ' . new \Ease\TWB\Widgets\LiveAge(filemtime($composer)) . ' ' . _('&copy; 2015-2024 Spoje.Net s.r.o.')
            )]
        ));

        $this->addItem(new \Ease\TWB\Container($rowFluid2));
    }

    /**
     * Zobrazí přehled právě přihlášených a spodek stránky.
     */
    public function finalize(): void
    {
        if (isset($this->webPage->heroUnit) && !count($this->webPage->heroUnit->pageParts)) {
            unset($this->webPage->container->pageParts['\Ease\Html\DivTag@heroUnit']);
        }

        $this->includeCss('/javascript/font-awesome/css/font-awesome.min.css');
    }
}
