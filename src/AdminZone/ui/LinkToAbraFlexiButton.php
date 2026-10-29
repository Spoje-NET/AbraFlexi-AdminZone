<?php

declare(strict_types=1);

namespace AdminZone\ui;

/**
 * Link to the record in the AbraFlexi web interface.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class LinkToAbraFlexiButton extends \Ease\Html\ATag
{
    /**
     * @param \AbraFlexi\RO $abraflexi record
     */
    public function __construct($abraflexi, array $properties = [])
    {
        parent::__construct(
            $abraflexi->getAbraFlexiURL(),
            '<i class="fa fa-arrow-up-right-from-square"></i> ' . _('Open in AbraFlexi') . ' <strong>' . str_replace('code:', '', (string) $abraflexi) . '</strong>',
            array_merge(['class' => 'btn btn-line btn-sm'], $properties),
        );
    }
}
