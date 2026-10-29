<?php

namespace AdminZone;

/**
 * Přihlašovací stránka.
 *
 * @author    Vitex <vitex@hippy.cz>
 * @copyright Vitex@hippy.cz (G) 2009,2011
 */

require_once 'includes/Init.php';

if (!is_object($oUser)) {
    die(_('Please enable cookies in your browser'));
}

$login = $oPage->getRequestValue('login');
if ($login) {
    try {
        $oUser = \Ease\Shared::user(new User($login));
    } catch (\PDOException $exc) {
        $oPage->addStatusMessage($exc->getTraceAsString(), 'error');
    }

//    \Ease\Shared::user()->SettingsColumn = 'settings';
    //$oUser = new User();
    if ($oUser->tryToLogin($_POST)) {
        session_commit();
        $oPage->redirect('index.php');
        exit;
    }

    $oUser = \Ease\Shared::user(new \Ease\Anonym());
} else {
    $oPage->addStatusMessage(_('Please enter your login creditials'));
}

$oPage->addItem(new ui\PageTop(_('Sign In')));

$loginFace = new \Ease\Html\DivTag(null, ['id' => 'LoginFace']);

$oPage->container->addItem($loginFace);

$loginRow = new \Ease\TWB\Row();
$infoColumn = $loginRow->addItem(new \Ease\TWB\Col(4));

$infoBlock = $infoColumn->addItem(new \Ease\TWB\Well(new \Ease\Html\ImgTag('images/password.png')));
$infoBlock->addItem(_('Welcome to Spoje.Net information system'));

$loginColumn = $loginRow->addItem(new \Ease\TWB\Col(4));

$submit = new \Ease\TWB\SubmitButton(_('Sign in'), 'success');

$loginPanel = new \Ease\TWB\Panel(
    new \Ease\Html\ImgTag('images/spoje-net_logo.gif'),
    'danger',
    null,
    $submit
);
$loginPanel->addItem(new \Ease\TWB\FormGroup(
    _('Username'),
    new \Ease\Html\InputTextTag('login', $login)
));
$loginPanel->addItem(new \Ease\TWB\FormGroup(
    _('Password'),
    new \Ease\Html\InputPasswordTag('password')
));
$loginPanel->addItem(new \Ease\TWB\FormGroup(
    _('One-time password (if required)'),
    new \Ease\Html\InputTextTag('otp')
));
$loginPanel->body->setTagCss(['margin' => '20px']);

$loginColumn->addItem($loginPanel);

$oPage->container->addItem(new \Ease\TWB\Form(['name' => 'Login', 'method' => 'POST'], $loginRow));

$oPage->addItem(new ui\PageBottom());

$oPage->draw();
