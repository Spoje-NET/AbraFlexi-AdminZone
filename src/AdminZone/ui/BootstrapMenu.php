<?php

/**
 * AdminZone - Stránka Webu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

class BootstrapMenu extends \Ease\TWB\Navbar
{
    /**
     * Navigace.
     *
     * @var \Ease\Html\UlTag
     */
    public $nav = null;

    /**
     * Hlavní menu aplikace.
     *
     * @param string $name
     * @param mixed  $content
     * @param array  $properties
     */
    public function __construct(
        $name = null,
        $content = null,
        $properties = null
    ) {
        parent::__construct(
            'Menu',
            (string)new \Ease\Html\ImgTag(
                'images/logo.png',
                _('System'),
                ['class' => 'img-rounded', 'height' => 24, 'width' => 24]
            ),
            ['class' => 'navbar-fixed-top']
        );

        $user = \Ease\Shared::user();
        \Ease\TWB\Part::twBootstrapize();

        switch (get_class($user)) {
            case 'AdminZone\User': //Admin
                break;
            case 'AbraFlexi\Bricks\Customer': //Customer
                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $this->addMenuItem(
                        new \Ease\TWB\LinkButton(
                            'cart.php',
                            '<img width=30 src=images/cart.svg> ' . _('Cart') . ' ' . count($_SESSION['cart'])
                        ),
                        'right'
                    );
                }

                break;
            case 'Ease\Anonym': //Anonymous
            default:
//                $this->addMenuItem('<a href="createaccount.php">'.\Ease\TWB\Part::GlyphIcon('leaf').' '._('Registrace').'</a>',
//                    'right');

                if (isset($_SESSION['cart']) && count($_SESSION['cart'])) {
                    $this->addMenuItem(
                        new \Ease\TWB\LinkButton(
                            'cart.php',
                            '<img width=30 src=images/cart.svg> ' . _('Cart') . ' ' . count($_SESSION['cart'])
                        ),
                        'right'
                    );
                }

                $this->addMenuItem(
                    '
<li class="divider-vertical"></li>
<li class="dropdown">
<a class="dropdown-Kimaie" href="adminlogin.php" data-Kimaie="dropdown"><i class="icon-circle-arrow-left"></i> ' . _('Sign In') . '<strong class="caret"></strong></a>
<div class="dropdown-menu" style="padding: 15px; padding-bottom: 0px; left: -120px;">
<form method="post" class="navbar-form navbar-left" action="adminlogin.php" accept-charset="UTF-8">
<input style="margin-bottom: 15px;" type="text" placeholder="' . _('Login') . '" id="username" name="username">
<input style="margin-bottom: 15px;" type="password" placeholder="' . _('Password') . '" id="password" name="password">
<!-- input style="float: left; margin-right: 10px;" type="checkbox" name="remember-me" id="remember-me" value="1">
<label class="string optional" for="remember-me"> ' . _('Remeber me') . '</label -->
<input class="btn btn-primary btn-block" type="submit" id="sign-in" value="' . _('Sign In') . '">
<!-- a href="newabraflexiuser.php" class="btn btn-info btn-block"><i class="fa fa-user"></i> ' . _('Registrace') . '</a -->
</form>
</div>',
                    'right'
                );

                break;
        }
    }

    /**
     * Show status messages
     */
    public function draw(): void
    {
        $statusMessages = WebPage::singleton()->getStatusMessagesAsHtml();
        if ($statusMessages) {
            $this->addItem(new \Ease\Html\DivTag(
                $statusMessages,
                ['id' => 'StatusMessages', 'class' => 'well', 'title' => _('Click to hide messages'),
                'data-state' => 'down']
            ));
            $this->addItem(new \Ease\Html\DivTag(null, ['id' => 'smdrag']));
            \AdminZone\ui\WebPage::singleton()->cleanMessages();
        }
        parent::draw();
    }
}
