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
            \Ease\WebPage::singleton()->setPageTitle((string) $pageTitle);
        }
    }

    public function finalize(): void
    {
        parent::finalize();
        $this->addItem(new MainMenu());
    }
}
