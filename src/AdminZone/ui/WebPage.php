<?php

declare(strict_types=1);

/**
 * AdminZone - web page (look and feel shared with AbraFlexi ClientZone).
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2026 Spoje.Net
 */

namespace AdminZone\ui;

class WebPage extends \Ease\TWB5\WebPage
{
    public const ASSET_VERSION = '1.1.1';

    public string $bootstrapCSS = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css';
    public string $jqueryJavaScript = 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js';
    public string $bootstrapThemeCSS = '';
    public \Ease\TWB5\Container $container;

    public function __construct(?string $pageTitle = null, mixed &$userObject = null)
    {
        if (null === $userObject) {
            $userObject = \Ease\Shared::user();
        }

        if ($pageTitle === null || $pageTitle === '') {
            $pageTitle = \Ease\Shared::appName() ?: _('AdminZone');
        }

        parent::__construct((string) $pageTitle);
        // remembered light / dark choice (js/vitex.js toggles it)
        $this->head->addItem('<script>(function(){var t="dark";try{t=localStorage.getItem("vsTheme")||t}catch(e){}var d=document.documentElement;d.classList.add("js");d.setAttribute("data-theme",t);d.setAttribute("data-bs-theme",t)})()</script>');
        $this->head->addItem('<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>');
        $this->includeCss('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600&family=Caveat:wght@400;500&display=swap');
        $this->includeCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css');
        $this->includeCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/v4-shims.min.css');
        $this->includeCss('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
        $this->includeCss('css/default.css');
        $this->includeCss('css/vitex.css?v=' . self::ASSET_VERSION);
        $this->includeJavaScript('js/vitex.js?v=' . self::ASSET_VERSION);
        $this->head->addItem('<link rel="icon" type="image/svg+xml" href="images/logo.svg">');
        $this->head->addItem('<meta name="theme-color" content="#1d1440">');
        $this->body->setTagID('page-top');
        $this->container = $this->addItem(new \Ease\TWB5\Container());
        $this->container->setTagClass('container-fluid page-content');
    }

    /**
     * Send anonymous visitor to the login page and stop the script.
     *
     * @param string      $loginPage login page address
     * @param null|string $message
     */
    public function onlyForLogged($loginPage = 'login.php', $message = null)
    {
        if (
            parent::onlyForLogged(
                $loginPage . '?backurl=' . urlencode($_SERVER['REQUEST_URI']),
                $message
            )
        ) {
            exit; // never let a page continue for anonymous visitor
        }

        return false;
    }
}
