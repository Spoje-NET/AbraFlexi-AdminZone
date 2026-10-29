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
     * Reason of the last failed sign in attempt.
     */
    public string $lastError = '';

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
            $this->lastError = _('missing login or password');

            return false;
        }

        $response = $this->requestToken($login, $password, $otp);

        if (empty($response['authSessionId'])) {
            $this->lastError = _('AbraFlexi sign in failed') . (empty($response['message']) ? '' : ': ' . $response['message']);

            return false;
        }

        $token = $response['authSessionId'];
        $obsolete = $this->isSessionObsolete($token);

        if ($obsolete && !self::hasServiceAccount()) {
            $this->lastError = _('AbraFlexi accepted the credentials but marked the session as obsolete, so the data cannot be read with it. Ask the AbraFlexi administrator to check the account (e.g. a required password change).');

            return false;
        }

        // the data calls may run under the service account: only listed AbraFlexi users may use it
        if (self::hasServiceAccount() && !self::isOperatorAllowed($login)) {
            $this->lastError = _('This AbraFlexi account is not allowed to use this application.');

            return false;
        }

        // keep the authorization tied to the operator whenever his own session works
        if (!$obsolete) {
            $this->setDataValue('authSessionId', $token);
            $companies = $this->availableCompanies();

            if ($companies !== [] && !isset($companies[(string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY')])) {
                $this->setDataValue('authSessionId', null);
                $this->lastError = sprintf(_('Your AbraFlexi account has no access to the company %s.'), (string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY'));

                return false;
            }
        }

        $this->setDataValue('login', $login);
        $this->setDataValue('authSessionId', $token);
        $this->setDataValue('refreshToken', $response['refreshToken'] ?? null);
        $this->setDataValue('csrfToken', $response['csrfToken'] ?? null);
        $this->setDataValue('companyName', $this->fetchCompanyName($token) ?? $this->serviceAccountCompanyName());
        $this->setDataValue('cookies', $response['_cookies'] ?? []);

        return $this->loginSuccess();
    }

    /**
     * Ask AbraFlexi for authSessionId (POST /login-logout/login, form encoded).
     *
     * @return array{authSessionId?:string,refreshToken?:string,message?:string}
     */
    protected function requestToken(string $login, string $password, ?string $otp): array
    {
        $form = ['username' => $login, 'password' => $password];

        if ($otp !== null) {
            $form['otp'] = $otp;
        }

        $verify = filter_var(\Ease\Shared::cfg('ABRAFLEXI_VERIFY_SSL', true), \FILTER_VALIDATE_BOOLEAN);
        $curl = curl_init(rtrim((string) \Ease\Shared::cfg('ABRAFLEXI_URL'), '/') . '/login-logout/login.json');
        curl_setopt_array($curl, [
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => http_build_query($form),
            \CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CONNECTTIMEOUT => 10,
            \CURLOPT_TIMEOUT => (int) \Ease\Shared::cfg('ABRAFLEXI_TIMEOUT', 15),
            // credentials travel here: certificate is verified unless explicitly disabled
            \CURLOPT_SSL_VERIFYPEER => $verify,
            \CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);
        $cookies = [];
        curl_setopt($curl, \CURLOPT_HEADERFUNCTION, static function ($ch, string $header) use (&$cookies): int {
            if (stripos($header, 'Set-Cookie:') === 0) {
                $cookies[] = trim(explode(';', substr($header, 11), 2)[0]);
            }

            return \strlen($header);
        });
        $caFile = (string) \Ease\Shared::cfg('ABRAFLEXI_CA_FILE', '');

        if ($caFile !== '') {
            curl_setopt($curl, \CURLOPT_CAINFO, $caFile); // self-signed AbraFlexi: trust its certificate instead of disabling checks
        }

        $body = curl_exec($curl);

        if ($body === false) {
            $message = sprintf(_('AbraFlexi is not reachable: %s'), curl_error($curl));
            curl_close($curl);

            return ['message' => $message];
        }

        $httpCode = curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        curl_close($curl);
        $decoded = json_decode((string) $body, true);
        error_log(sprintf('AdminZone login: HTTP %d, response keys: %s, cookies: %s', $httpCode, \is_array($decoded) ? implode(',', array_keys($decoded)) : 'not json', implode(',', array_map(static fn ($c) => explode('=', $c)[0], $cookies))));
        if (\is_array($decoded)) {
            $decoded['_cookies'] = $cookies;
        }


        if (!\is_array($decoded)) {
            return ['message' => _('Unexpected response from AbraFlexi')];
        }

        if (empty($decoded['success'])) {
            return ['message' => (string) ($decoded['message'] ?? $decoded['reason'] ?? '')];
        }

        return $decoded;
    }

    /**
     * AbraFlexi server and company this operator works with.
     *
     * @return array{url:string,host:string,company:string,companyName:string}
     */
    public function getServerInfo(): array
    {
        $url = (string) \Ease\Shared::cfg('ABRAFLEXI_URL');
        $company = (string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY');
        $parts = parse_url($url);
        $host = ($parts['host'] ?? $url) . (isset($parts['port']) ? ':' . $parts['port'] : '');

        return [
            'url' => $url,
            'host' => $host,
            'company' => $company,
            'companyName' => (string) ($this->getDataValue('companyName') ?: $company),
        ];
    }

    /**
     * Is a service account (ABRAFLEXI_LOGIN / ABRAFLEXI_PASSWORD) configured for reading and writing data?
     *
     * The operator's token then only proves who signed in.
     */
    public static function hasServiceAccount(): bool
    {
        return !empty(\Ease\Shared::cfg('ABRAFLEXI_LOGIN')) && !empty(\Ease\Shared::cfg('ABRAFLEXI_PASSWORD'));
    }

    /**
     * Optional allow list (ABRAFLEXI_ALLOWED_USERS, comma separated AbraFlexi logins).
     *
     * Empty list means every successfully authenticated AbraFlexi user may use the application.
     */
    public static function isOperatorAllowed(string $login): bool
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) \Ease\Shared::cfg('ABRAFLEXI_ALLOWED_USERS', ''))));

        return $allowed === [] || \in_array($login, $allowed, true);
    }

    /**
     * Display name of the configured company read with the service account (name of this company only).
     */
    protected function serviceAccountCompanyName(): ?string
    {
        if (!self::hasServiceAccount()) {
            return null;
        }

        $verify = filter_var(\Ease\Shared::cfg('ABRAFLEXI_VERIFY_SSL', true), \FILTER_VALIDATE_BOOLEAN);
        $curl = curl_init(rtrim((string) \Ease\Shared::cfg('ABRAFLEXI_URL'), '/') . '/c.json');
        curl_setopt_array($curl, [
            \CURLOPT_USERPWD => \Ease\Shared::cfg('ABRAFLEXI_LOGIN') . ':' . \Ease\Shared::cfg('ABRAFLEXI_PASSWORD'),
            \CURLOPT_HTTPHEADER => ['Accept: application/json'],
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CONNECTTIMEOUT => 5,
            \CURLOPT_TIMEOUT => 10,
            \CURLOPT_SSL_VERIFYPEER => $verify,
            \CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);
        $caFile = (string) \Ease\Shared::cfg('ABRAFLEXI_CA_FILE', '');

        if ($caFile !== '') {
            curl_setopt($curl, \CURLOPT_CAINFO, $caFile);
        }

        $body = curl_exec($curl);
        curl_close($curl);
        $companies = \is_string($body) ? (json_decode($body, true)['companies']['company'] ?? []) : [];
        $wanted = (string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY');

        foreach (\is_array($companies) ? $companies : [] as $company) {
            if (($company['dbNazev'] ?? null) === $wanted && !empty($company['nazev'])) {
                return (string) $company['nazev'];
            }
        }

        return null;
    }

    /**
     * Does AbraFlexi consider the fresh session obsolete (unusable for the API)?
     */
    protected function isSessionObsolete(string $token): bool
    {
        $verify = filter_var(\Ease\Shared::cfg('ABRAFLEXI_VERIFY_SSL', true), \FILTER_VALIDATE_BOOLEAN);
        $curl = curl_init(rtrim((string) \Ease\Shared::cfg('ABRAFLEXI_URL'), '/') . '/login-logout/session-keep-alive.js');
        curl_setopt_array($curl, [
            \CURLOPT_HTTPHEADER => ['X-authSessionId: ' . $token, 'Accept: application/json'],
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CONNECTTIMEOUT => 5,
            \CURLOPT_TIMEOUT => 10,
            \CURLOPT_SSL_VERIFYPEER => $verify,
            \CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);
        $caFile = (string) \Ease\Shared::cfg('ABRAFLEXI_CA_FILE', '');

        if ($caFile !== '') {
            curl_setopt($curl, \CURLOPT_CAINFO, $caFile);
        }

        $body = curl_exec($curl);
        curl_close($curl);
        $state = \is_string($body) ? json_decode($body, true) : null;

        return \is_array($state) && ($state['obsolete'] ?? false) === true;
    }

    /**
     * Companies of the AbraFlexi server visible to this operator.
     *
     * @return array<string,string> company code (dbNazev) => display name
     */
    public function availableCompanies(): array
    {
        $token = $this->getAuthSessionId();

        if (!$token) {
            return [];
        }

        $verify = filter_var(\Ease\Shared::cfg('ABRAFLEXI_VERIFY_SSL', true), \FILTER_VALIDATE_BOOLEAN);
        $curl = curl_init(rtrim((string) \Ease\Shared::cfg('ABRAFLEXI_URL'), '/') . '/c.json');
        curl_setopt_array($curl, [
            \CURLOPT_HTTPHEADER => ['X-authSessionId: ' . $token, 'Accept: application/json'],
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CONNECTTIMEOUT => 5,
            \CURLOPT_TIMEOUT => 10,
            \CURLOPT_SSL_VERIFYPEER => $verify,
            \CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);
        $caFile = (string) \Ease\Shared::cfg('ABRAFLEXI_CA_FILE', '');

        if ($caFile !== '') {
            curl_setopt($curl, \CURLOPT_CAINFO, $caFile);
        }

        $body = curl_exec($curl);
        curl_close($curl);
        $companies = \is_string($body) ? (json_decode($body, true)['companies']['company'] ?? []) : [];
        $found = [];

        foreach (\is_array($companies) ? $companies : [] as $company) {
            if (!empty($company['dbNazev'])) {
                $found[(string) $company['dbNazev']] = (string) ($company['nazev'] ?? $company['dbNazev']);
            }
        }

        return $found;
    }

    /**
     * Display name of the configured company (best effort, null when unknown).
     */
    protected function fetchCompanyName(string $token): ?string
    {
        $this->setDataValue('authSessionId', $token);

        return $this->availableCompanies()[(string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY')] ?? null;
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
            // ending the session at AbraFlexi is best effort: signing out here must never fail
            try {
                $rest = new \AbraFlexi\RO(null, [
                    'url' => \Ease\Shared::cfg('ABRAFLEXI_URL'),
                    'authSessionId' => $token,
                    'timeout' => 5,
                ]);
                $rest->logout($this->getUserLogin());
            } catch (\Throwable $exc) {
                error_log('AdminZone logout at AbraFlexi failed: ' . $exc->getMessage());
            }
        }

        $this->setDataValue('authSessionId', null);
        $this->setDataValue('csrfToken', null);
        $this->setDataValue('cookies', []);

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
