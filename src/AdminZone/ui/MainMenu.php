<?php

/**
 * AdminZone - Hlavní menu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

class MainMenu extends \Ease\Html\NavTag
{
    /**
     * Vytvoří hlavní menu.
     */
    public function __construct()
    {
        parent::__construct(null, ['id' => 'MainMenu']);
    }

    /**
     * Vložení menu.
     */
    public function afterAdd(): void
    {
        $nav = $this->addItem(new BootstrapMenu());
        $user = \Ease\Shared::user();
        $userID = $user->getUserID();

        switch (get_class($user)) {
            case 'AdminZone\User': //Admin
                $nav->addMenuItem(new NavBarSearchBox('search', 'search.php'));
                $nav->addDropDownMenu(
                    '<img width=30 src=images/gear.svg> ' . _('Scripts'),
                    [
                            'invoicematch.php' => \Ease\TWB\Part::GlyphIcon('piggy-bank') . ' ' . _('Invoice matching'),
                            'generujfakturyzesmluv.php' => \Ease\TWB\Part::GlyphIcon('duplicate') . '&nbsp;' . _('Generate Invoices from contracts'),
                            'sendreminders.php' => \Ease\TWB\Part::GlyphIcon('bullhorn') . '&nbsp;' . _('Send Reminders'),
                            'unblocknet.php' => \Ease\TWB\Part::GlyphIcon('ok') . '&nbsp;' . _('Unblock All ISP Clienta'),
                        ]
                );
                $nav->addDropDownMenu(
                    '<img width=30 src=images/contract_150.png> ' . _('Oders'),
                    array_merge([
                    'contracttodo.php' => new \Ease\Html\ImgTag(
                        'images/copying.svg',
                        'TODO',
                        ['height' => '20px']
                    ) . '&nbsp; ' . _('Orders TODO'),
                    'apitodo.php' => new \Ease\Html\ImgTag(
                        'images/api.svg',
                        'API',
                        ['height' => '20px']
                    ) . '&nbsp; ' . _('API TODO'),
                    //                    'zavazky.php' => \Ease\TWB\Part::GlyphIcon('transfer').' '._('Měsíční závazky'),
                    //                    'pohledavky.php' => \Ease\TWB\Part::GlyphIcon('transfer').' '._('Měsíční pohledávky'),
                    //                    'contract-reset.php' => \Ease\TWB\Part::GlyphIcon('repeat').' '._('Reset autogenerace'),
                    //                    'contract.php' => \Ease\TWB\Part::GlyphIcon('plus').' '._('Nová smlouva'),
                    //                    'contracts.php' => \Ease\TWB\Part::GlyphIcon('list').'&nbsp;'._('Přehled smluv'),
                    //                    'rspcntrcts.php' => \Ease\TWB\Part::GlyphIcon('user').'&nbsp;'._('Respondenti'),
                        ])
                );

                if (!empty(\Ease\Shared::cfg('PROD_CAT'))) {
                    $nav->addDropDownMenu(
                        '<img width=30 src=images/order.svg> ' . _('Proposal'),
                        [
                                'adminpricelist.php' => \Ease\TWB\Part::GlyphIcon('th-list') . ' ' . _('Pricelist'),
                            ]
                    );
                }

                $nav->addMenuItem(new \Ease\Html\ATag(
                    'clients.php',
                    _('Clients')
                ));

                $nav->addDropDownMenu(
                    '<img width=30 src=images/gear.svg> ' . _('Integrations'),
                    [
                        'extids.php?service=ipex' => \Ease\TWB\Part::GlyphIcon('earphone') . '&nbsp;' . _('IPEX'),
                        'extids.php?service=subreg' => \Ease\TWB\Part::GlyphIcon('globe') . '&nbsp;' . _('Subreg'),
                        'extids.php?service=sltv' => \Ease\TWB\Part::GlyphIcon('facetime-video') . '&nbsp;' . _('SledovaniTV'),
                    ]
                );

                $nav->addDropDownMenu(
                    '<img width=30 src=images/invoice.svg> ' . _('Fakturace'),
                    [
                            'dluznici.php' => \Ease\TWB\Part::GlyphIcon('th-list') . ' ' . _('Dlužníci'),
                            'topay.php' => \Ease\TWB\Part::GlyphIcon('th-list') . ' ' . _('Třeba uhradit'),
                        ]
                );

                $nav->addDropDownMenu(
                    '<img width=20 src=images/report.svg> ' . _('Digest'),
                    [
                            'digest.php' => \Ease\TWB\Part::GlyphIcon('stats') . ' ' . _('All digests'),
                    //                        'dashboard.php' => \Ease\TWB\Part::GlyphIcon('dashboard').' '._('Dashboard'),
                    //                    'daydigest.php' => \Ease\TWB\Part::GlyphIcon('stats').' '._('Dayly'),
                    //                    'weekdigest.php' => \Ease\TWB\Part::GlyphIcon('stats').' '._('Weekly'),
                    //                    'monthdigest.php' => \Ease\TWB\Part::GlyphIcon('stats').' '._('Monthly'),
                    //                    'yeardigest.php' => \Ease\TWB\Part::GlyphIcon('stats').' '._('Yearly'),
                        ]
                );

                $nav->addDropDownMenu(
                    '<img width=30 src=' . $user->getIcon() . '> ' . $user->getUserLogin(),
                    array_merge([
                    'settings.php' => \Ease\TWB\Part::GlyphIcon('wrench') . ' ' . _('Settings'),
                    'about.php' => \Ease\TWB\Part::GlyphIcon('info-sign') . '&nbsp;' . _('About'),
                    '' => '',
                    'logout.php' => \Ease\TWB\Part::GlyphIcon('off') . ' ' . _('Sign Out'),
                        ]),
                    'right'
                );

                if ($user->getSettingValue('admin')) { //Superuser
//                    $userMenu .= '<li><a href="overview.php">'.\Ease\TWB\Part::GlyphIcon('list').' '._('Admin Item').'</a></li>';
                }




                break;
            case 'AbraFlexi\Bricks\Customer': //Customer
                if (!empty(\Ease\Shared::cfg('PROD_CAT'))) {
                    $nav->addMenuItem(new \Ease\Html\ATag(
                        'pricelist.php',
                        '<img width=30 src=images/cennik.png> ' . _('Pricelist')
                    ));
                }
                $subregId = $user->getAdresar()->getExternalID('subreg');
                $ipexId = $user->getAdresar()->getExternalID('ipex');

                if ($subregId || $ipexId) {
                    $productsMenuItems = [];
                    if ($ipexId) {
                        $productsMenuItems['voip.php'] = \Ease\TWB\Part::GlyphIcon('th-list') . ' ' . _('VoIP');
                    }
                    if ($subregId) {
                        $productsMenuItems['dns.php'] = \Ease\TWB\Part::GlyphIcon('th-list') . ' ' . _('Domains');
                    }
                    if (!empty(\Ease\Shared::cfg('PROD_CAT'))) {
                        $nav->addDropDownMenu(
                            '<img width=30 src=images/products.svg> ' . _('Products'),
                            $productsMenuItems
                        );
                    }
                }

                $nav->addDropDownMenu(
                    '<img width=30 src=images/order.svg> ' . _('Orders'),
                    [
                            'orderform.php' => \Ease\TWB\Part::GlyphIcon('plus') . ' ' . _('New order'),
                    //                    'pricelist.php' => \Ease\TWB\Part::GlyphIcon('th-list').' '._('Pricelist'),
                            'myorders.php' => \Ease\TWB\Part::GlyphIcon('list') . '&nbsp;' . _('My orders')]
                );

                $nav->addDropDownMenu(
                    '<img width=30 src=' . $user->getIcon() . '> ' . $user->getUserLogin(),
                    [
                            'changecustpw.php' => \Ease\TWB\Part::GlyphIcon('lock') . ' ' . _('Password change'),
                            'settings.php' => \Ease\TWB\Part::GlyphIcon('wrench') . ' ' . _('Settings'),
                            '' => '-',
                            'logout.php' => \Ease\TWB\Part::GlyphIcon('off') . '&nbsp;' . _('Sign Out')],
                    'right'
                );

                break;
            case 'Ease\Anonym': //Anonymous
            default:
                break;
        }
    }

    /**
     * Přidá do stránky javascript pro skrývání oblasti stavových zpráv.
     */
    public function finalize(): void
    {
        $this->addCss('body {
                padding-top: 60px;
                padding-bottom: 40px;
            }');

        WebPage::singleton()->addCss('.dropdown-menu { overflow-y: auto } ');
        WebPage::singleton()->addJavaScript(
            "$('.dropdown-menu').css('max-height',$(window).height()-100);",
            null,
            true
        );
        $this->includeJavaScript('js/slideupmessages.js');
    }
}
