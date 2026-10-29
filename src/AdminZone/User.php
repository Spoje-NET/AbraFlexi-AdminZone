<?php

declare(strict_types=1);

/**
 * AdminZone - Uživatel ověřený přes AbraFlexi.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2026 Spoje.Net
 */

namespace AdminZone;

/**
 * AdminZone operator signed in by AbraFlexi authSessionId token.
 *
 * No local database: credentials are verified by POST /login-logout/login
 * and the returned token is kept (in session data) and reused for API calls.
 *
 * @see https://podpora.flexibee.eu/cs/articles/3422248-vygenerovani-autentizacniho-tokenu
 */
class User extends \Ease\User
{
    public string $nameColumn = 'login';

    /**
     * @param int|string $userID unused, kept for compatibility
     */
    public function __construct($userID = null)
    {
        parent::__construct();
    }

    /**
     * Sign in with AbraFlexi credentials (optionally with one-time password).
     *
     * @param array<string,string> $formData login, password[, otp]
     */
    public function tryToLogin(array $formData): bool
    {
        $login = trim((string) ($formData['login'] ?? ''));
        $password = (string) ($formData['password'] ?? '');
        $otp = ($formData['otp'] ?? '') !== '' ? (string) $formData['otp'] : null;

        if ($login === '' || $password === '') {
            $this->addStatusMessage(_('missing login or password'), 'error');

            return false;
        }

        $rest = new \AbraFlexi\RO(null, [
            'url' => \Ease\Shared::cfg('ABRAFLEXI_URL'),
            'company' => \Ease\Shared::cfg('ABRAFLEXI_COMPANY'),
            'user' => $login,
            'password' => $password,
        ]);

        $token = $rest->requestAuthSessionID($login, $password, $otp);

        if (empty($token) || $rest->lastResponseCode !== 200) {
            $this->addStatusMessage(_('AbraFlexi sign in failed'), 'error');

            return false;
        }

        $this->setDataValue('login', $login);
        $this->setDataValue('authSessionId', $token);
        $this->setDataValue('refreshToken', $rest->refreshToken ?? null);

        return $this->loginSuccess();
    }

    public function loginSuccess()
    {
        $this->userID = 0;
        $this->setUserLogin((string) $this->getDataValue('login'));
        $this->logged = true;
        $this->addStatusMessage(sprintf(_('Signed in as %s'), $this->getUserLogin()), 'success');
        $this->setObjectName();

        return true;
    }

    /**
     * AbraFlexi authSessionId of this operator.
     */
    public function getAuthSessionId(): ?string
    {
        $token = $this->getDataValue('authSessionId');

        return empty($token) ? null : (string) $token;
    }

    /**
     * Every AbraFlexi-authenticated operator is administrator of this zone.
     */
    public function getSettingValue(string $settingName): ?string
    {
        return $settingName === 'admin' ? '1' : null;
    }

    public function setSettingValue($settingName, $settingValue): bool
    {
        return true;
    }

    public function setSettings($settings): bool
    {
        return true;
    }

    public function logout(): bool
    {
        $token = $this->getAuthSessionId();
        if ($token) {
            $rest = new \AbraFlexi\RO(null, [
                'url' => \Ease\Shared::cfg('ABRAFLEXI_URL'),
                'authSessionId' => $token,
            ]);
            $rest->logout($this->getUserLogin());
        }

        return parent::logout();
    }

    public function getUserName(): string
    {
        return $this->getUserLogin();
    }

    public function getUserID(): int
    {
        return 0;
    }
}
