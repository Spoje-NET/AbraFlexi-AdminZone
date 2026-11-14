<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdminZone\ui;

use AdminZone\PricelistChecker;
use AdminZone\PricelistFinding;

/**
 * Result of the pricelist check for the operator: counts per severity and the findings.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PricelistReport extends \Ease\Html\DivTag
{
    private const BADGE = [
        PricelistFinding::ERROR => 'danger',
        PricelistFinding::WARNING => 'warning',
        PricelistFinding::INFO => 'secondary',
    ];

    /**
     * @param array<int, array<string, mixed>> $items    pricelist rows that were checked
     * @param array<int, PricelistFinding>     $findings what the checker found
     * @param null|string                      $show     show only this severity, null for all
     * @param string                           $itemUrl  base URL of the pricelist item in AbraFlexi (the id is appended)
     */
    public function __construct(array $items, array $findings, ?string $show = null, string $itemUrl = '')
    {
        parent::__construct();

        $counts = array_fill_keys(PricelistFinding::SEVERITIES, 0);

        foreach ($findings as $finding) {
            ++$counts[$finding->severity];
        }

        $offered = 0;
        $checker = new PricelistChecker();

        foreach ($items as $item) {
            $offered += $checker->isOffered($item) ? 1 : 0;
        }

        $this->addItem(new \Ease\Html\PTag(
            sprintf(_('Checked %d pricelist items, %d of them offered by ClientZone (labels %s and %s).'), \count($items), $offered, PricelistChecker::LABEL_SHOP, PricelistChecker::LABEL_ACTIVE),
            ['class' => 'dim'],
        ));
        $this->addItem($this->summary($counts, $show));

        $shown = array_values(array_filter(
            $findings,
            static fn (PricelistFinding $finding): bool => $show === null || $finding->severity === $show,
        ));

        if ($shown === []) {
            $this->addItem(new \Ease\Html\DivTag(
                $findings === [] ? _('Every pricelist item is entered correctly.') : _('Nothing of this severity.'),
                ['class' => 'alert alert-success mt-3'],
            ));

            return;
        }

        $table = new \Ease\Html\TableTag(null, ['class' => 'table table-sm table-hover mt-3']);
        $table->addRowHeaderColumns([_('Severity'), _('Item'), _('Problem'), _('Check')]);

        foreach ($shown as $finding) {
            $code = htmlspecialchars($finding->code, \ENT_QUOTES);
            $table->addRowColumns([
                '<span class="badge bg-'.self::BADGE[$finding->severity].'">'.self::label($finding->severity).'</span>',
                $itemUrl !== '' && $finding->itemId > 0
                    ? new \Ease\Html\ATag($itemUrl.$finding->itemId, '<strong>'.$code.'</strong>', ['target' => '_blank', 'rel' => 'noopener'])
                    : '<strong>'.$code.'</strong>',
                htmlspecialchars($finding->message, \ENT_QUOTES),
                '<code>'.htmlspecialchars($finding->rule, \ENT_QUOTES).'</code>',
            ]);
        }

        $this->addItem($table);
    }

    /**
     * Human name of a severity.
     */
    public static function label(string $severity): string
    {
        return match ($severity) {
            PricelistFinding::ERROR => _('Error'),
            PricelistFinding::WARNING => _('Warning'),
            default => _('Notice'),
        };
    }

    /**
     * Counters that double as filters.
     *
     * @param array<string, int> $counts
     */
    private function summary(array $counts, ?string $show): \Ease\Html\DivTag
    {
        $row = new \Ease\Html\DivTag(null, ['class' => 'd-flex gap-2 my-3']);
        $row->addItem(new \Ease\Html\ATag(
            'pricelist.php',
            _('All').' <span class="badge bg-dark">'.array_sum($counts).'</span>',
            ['class' => 'btn btn-sm '.($show === null ? 'btn-glow' : 'btn-line')],
        ));

        foreach ($counts as $severity => $count) {
            $row->addItem(new \Ease\Html\ATag(
                'pricelist.php?severity='.$severity,
                self::label($severity).' <span class="badge bg-'.self::BADGE[$severity].'">'.$count.'</span>',
                ['class' => 'btn btn-sm '.($show === $severity ? 'btn-glow' : 'btn-line')],
            ));
        }

        return $row;
    }
}
