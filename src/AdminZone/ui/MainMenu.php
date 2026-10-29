<?php

declare(strict_types=1);

namespace AdminZone\ui;

/**
 * Main menu: pairing of external accounts and customers.
 */
class MainMenu extends \Ease\Html\DivTag
{
    public function __construct()
    {
        parent::__construct(null, ['id' => 'MainMenu']);
    }

    /**
     * Insert the menu bar.
     */
    public function afterAdd(): void
    {
        if (!empty($this->pageParts)) {
            return;
        }

        $nav = $this->addItem(new BootstrapMenu());
        $user = \Ease\Shared::user();

        if (!$user instanceof \AdminZone\User || !$user->isLogged()) {
            $nav->addMenuItem(
                new \Ease\Html\ATag('login.php', _('Sign In'), ['class' => 'btn btn-glow btn-sm ms-2']),
                true,
                'right',
            );

            return;
        }

        $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
        $nav->addMenuItem(new \Ease\Html\ATag('clients.php', '<i class="fa fa-users"></i> ' . _('Clients')));
        $nav->addDropDownMenu(
            '<i class="fa fa-link"></i> ' . _('Integrations'),
            [
                'extids.php?service=ipex' => '<i class="fa fa-phone"></i> ' . _('IPEX'),
                'extids.php?service=subreg' => '<i class="fa fa-globe"></i> ' . _('Subreg'),
                'extids.php?service=sltv' => '<i class="fa fa-tv"></i> ' . _('SledovaniTV'),
            ],
        );
        $nav->addDropDownMenu(
            '<i class="fa fa-user"></i> ' . $user->getUserLogin(),
            [
                'logout.php' => '<i class="fa fa-power-off"></i> ' . _('Sign Out'),
            ],
            'right',
        );
    }
}
