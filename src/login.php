<?php

declare(strict_types=1);

namespace AdminZone;

/**
 * Přihlašovací stránka - přihlášení účtem AbraFlexi.
 *
 * @author    Vitex <vitex@hippy.cz>
 * @copyright 2009-2026 Spoje.Net
 */

require_once 'includes/Init.php';

if (!is_object($oUser)) {
    die(_('Please enable cookies in your browser'));
}

$login = trim((string) $oPage->getRequestValue('login'));
$backUrl = (string) $oPage->getRequestValue('backurl');
$loginError = '';

if ($login !== '') {
    $oUser = \Ease\Shared::user(new User());

    if ($oUser->tryToLogin($_POST)) {
        session_commit();
        // only local relative targets are allowed to avoid open redirect
        $oPage->redirect(preg_match('#^/?[A-Za-z0-9_./?=&%-]+$#', $backUrl) && !str_contains($backUrl, '//') ? $backUrl : 'index.php');

        exit;
    }

    $loginError = $oUser->lastError ?: _('Sign in failed');
    $oUser = \Ease\Shared::user(new \Ease\Anonym());
}

$oPage->addToHeader(new ui\PageTop(_('Sign In')));

$wrap = new \Ease\Html\DivTag(null, ['class' => 'cz-auth']);
$card = $wrap->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-card']));
$brand = $card->addItem(new \Ease\Html\DivTag(null, ['class' => 'cz-auth-brand']));
$brand->addItem(new \Ease\Html\ImgTag(
    'images/logo.svg',
    \Ease\Shared::appName(),
    ['width' => 56, 'height' => 56, 'class' => 'cz-auth-logo'],
));
$brand->addItem(new \Ease\Html\DivTag(_('Sign In'), ['class' => 'eyebrow']));
$brand->addItem(new \Ease\Html\H1Tag(\Ease\Shared::appName()));
$brand->addItem(new \Ease\Html\PTag(_('Sign in with your AbraFlexi account'), ['class' => 'dim']));

if ($loginError !== '') {
    $card->addItem(new \Ease\Html\DivTag($loginError, ['class' => 'alert alert-danger', 'role' => 'alert']));
}

$form = $card->addItem(new \Ease\TWB5\Form(['name' => 'Login', 'method' => 'POST', 'class' => 'cz-auth-form']));
$form->addItem(new \Ease\Html\InputHiddenTag('backurl', $backUrl));
$form->addItem(new \Ease\Html\LabelTag('login', _('Username'), ['class' => 'form-label']));
$form->addItem(new \Ease\Html\InputTextTag('login', $login, [
    'id' => 'login',
    'class' => 'form-control form-control-lg',
    'autocomplete' => 'username',
    'required' => 'required',
]));
$form->addItem(new \Ease\Html\LabelTag('password', _('Password'), ['class' => 'form-label mt-3']));
$form->addItem(new \Ease\Html\InputPasswordTag('password', null, [
    'id' => 'password',
    'class' => 'form-control form-control-lg',
    'autocomplete' => 'current-password',
    'required' => 'required',
]));
$form->addItem(new \Ease\Html\LabelTag('otp', _('One-time password (if required)'), ['class' => 'form-label mt-3']));
$form->addItem(new \Ease\Html\InputTextTag('otp', null, [
    'id' => 'otp',
    'class' => 'form-control',
    'autocomplete' => 'one-time-code',
]));
$form->addItem(new \Ease\Html\DivTag(
    new \Ease\Html\ButtonTag(_('Sign in') . ' <span class="arrow">→</span>', [
        'type' => 'submit',
        'class' => 'btn btn-glow btn-lg w-100',
    ]),
    ['class' => 'mt-4'],
));

$oPage->container->addItem($wrap);
$oPage->addItem(new ui\PageBottom());

$oPage->draw();
