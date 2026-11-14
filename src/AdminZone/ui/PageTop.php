<?php

declare(strict_types=1);

namespace AdminZone\ui;

/**
 * Page head: title and main menu.
 */
class PageTop extends \Ease\Html\DivTag
{
    public function __construct($pageTitle = null)
    {
        parent::__construct();

        if ($pageTitle !== null && $pageTitle !== '') {
            // The title goes into <title> unescaped by the library and may hold data from AbraFlexi
            \Ease\WebPage::singleton()->setPageTitle(htmlspecialchars((string) $pageTitle, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8'));
        }
    }

    public function finalize(): void
    {
        parent::finalize();
        $this->addItem(new MainMenu());
    }
}
