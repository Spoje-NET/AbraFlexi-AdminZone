<?php

namespace AdminZone;

/**
 * Dotazník - administrace.
 *
 * @author     Vítězslav Dvořák <dvorak@austro-bohemia.cz>
 * @copyright  2015 Austro-Bohemia s.r.o.
 */

require_once 'includes/Init.php';

$oPage->onlyForLogged();

$oPage->addItem(new ui\PageTop(_('Přehled výsledků')));

$infoBlock = $oPage->container->addItem(
    new \Ease\TWB\Panel(
        _('O Programu'),
        'info',
        null,
        new \Ease\TWB\LinkButton(
            'http://v.s.cz/',
            _('Vitex Software'),
            'info'
        )
    )
);
$listing = $infoBlock->addItem(new \Ease\Html\UlTag());

if (file_exists('../README.md')) {
    $listing->addItem(implode('<br>', file('../README.md')));
} else {
    if (file_exists('/usr/share/doc/abraflexi-adminzone/README.md')) {
        $listing->addItem(implode('<br>', file('/usr/share/doc/abraflexi-adminzone/README.md')));
    }
}

$oPage->draw();
