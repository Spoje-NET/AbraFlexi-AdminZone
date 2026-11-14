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

use AdminZone\CsrfToken;
use AdminZone\PricelistChecker;
use AdminZone\PricelistItem;
use AdminZone\PricelistTree;

/**
 * Editor of a pricelist item: the operator picks the stock type, the labels and the place in the tree
 * from what AbraFlexi and ClientZone know, instead of typing codes.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class PricelistItemForm extends \Ease\TWB5\Form
{
    /**
     * @param array<string, mixed>                                                                     $values  current values of the item (columns of "cenik")
     * @param array{labels: array<string, string>, units: array<string, string>, groups: array<string, string>} $choices result of PricelistItem::fetchChoices()
     * @param PricelistTree                                                                            $tree    the pricelist tree
     * @param null|int                                                                                 $node    node the item is placed in
     * @param null|int                                                                                 $itemId  id of the edited item, null for a new one
     */
    public function __construct(array $values, array $choices, PricelistTree $tree, ?int $node, ?int $itemId, string $action = 'pricelistitem.php')
    {
        parent::__construct(['name' => 'pricelistitem', 'action' => $action, 'method' => 'post'], ['class' => 'row g-3']);

        $this->addItem(new \Ease\Html\InputHiddenTag(CsrfToken::FIELD, CsrfToken::get()));

        if ($itemId !== null) {
            $this->addItem(new \Ease\Html\InputHiddenTag('id', (string) $itemId));
        }

        $left = new \Ease\Html\DivTag(null, ['class' => 'col-lg-6']);
        $right = new \Ease\Html\DivTag(null, ['class' => 'col-lg-6']);

        $left->addItem(self::field(_('Code'), 'kod', self::input('kod', self::value($values, 'kod'), ['maxlength' => '30', 'required' => 'required']), _('ClientZone finds the item by this code')));
        $left->addItem(self::field(_('Name'), 'nazev', self::input('nazev', self::value($values, 'nazev'), ['maxlength' => '255', 'required' => 'required'])));
        $left->addItem(self::field(_('Description for customers'), 'popis', '<textarea class="form-control" id="popis" name="popis" rows="4">'.self::e(self::value($values, 'popis')).'</textarea>'));

        $price = self::value($values, 'cenaZakl');
        $left->addItem(self::field(_('Price without VAT'), 'cenaZakl', self::input('cenaZakl', is_numeric($price) ? rtrim(rtrim(number_format((float) $price, 2, '.', ''), '0'), '.') : '', ['inputmode' => 'decimal'])));
        $left->addItem(self::field(_('VAT rate'), 'typSzbDphK', self::select('typSzbDphK', PricelistItem::vatChoices(), self::value($values, 'typSzbDphK') ?: 'typSzbDph.dphZakl')));
        $left->addItem(self::field(
            _('Unit of measure'),
            'mj1',
            self::select('mj1', $choices['units'], self::codeOf(self::value($values, 'mj1')), _('(none)'), true),
            _('For a service it also tells the billing period, e.g. month'),
        ));
        $left->addItem(self::field(_('Goods group'), 'skupZboz', self::select('skupZboz', $choices['groups'], self::codeOf(self::value($values, 'skupZboz')), _('(none)'), true)));

        $validity = '<div class="input-group">'
            .'<span class="input-group-text">'.self::e(_('from year')).'</span>'
            .self::input('platiOd', self::value($values, 'platiOd') ?: '0', ['inputmode' => 'numeric', 'maxlength' => '4'])
            .'<span class="input-group-text">'.self::e(_('to year')).'</span>'
            .self::input('platiDo', self::value($values, 'platiDo') ?: '9999', ['inputmode' => 'numeric', 'maxlength' => '4'])
            .'</div>';
        $left->addItem(self::field(_('Validity'), 'platiOd', $validity, _('0 and 9999 mean no limit')));

        $right->addItem($this->stockTypes(self::value($values, 'typZasobyK')));
        $right->addItem($this->labels(PricelistChecker::labels($values['stitky'] ?? ''), $choices['labels']));
        $right->addItem($this->placement($tree, $node));

        $this->addItem($left);
        $this->addItem($right);
        $this->addItem(new \Ease\Html\DivTag(
            '<button type="submit" class="btn btn-success"><i class="fa fa-floppy-disk"></i> '.self::e(_('Save')).'</button>'
            .' <a class="btn btn-outline-secondary" href="pricelisttree.php'.($node !== null ? '?node='.$node : '').'">'.self::e(_('Back to the tree')).'</a>',
            ['class' => 'col-12 text-end'],
        ));
    }

    /**
     * Stock type as a choice of cards: what ClientZone makes of an order is written on each.
     */
    private function stockTypes(string $current): \Ease\Html\DivTag
    {
        $box = new \Ease\Html\DivTag(null, ['class' => 'mb-3']);
        $box->addItem('<label class="form-label fw-semibold">'.self::e(_('Stock type')).'</label>');

        foreach (PricelistItem::stockTypeChoices() as $type => $choice) {
            $id = 'type_'.preg_replace('/\W/', '_', $type);
            $box->addItem(
                '<div class="form-check'.($choice['supported'] ? '' : ' text-muted').'">'
                .'<input class="form-check-input" type="radio" name="typZasobyK" id="'.$id.'" value="'.self::e($type).'"'.($type === $current ? ' checked' : '').'>'
                .'<label class="form-check-label" for="'.$id.'">'.self::e($choice['label'])
                .' <small class="'.($choice['supported'] ? 'text-success' : 'text-warning').'">— '.self::e($choice['note']).'</small></label>'
                .'</div>',
            );
        }

        return $box;
    }

    /**
     * Labels as checkboxes; the two that make ClientZone offer the item come first.
     *
     * @param array<int, string>    $current labels the item has
     * @param array<string, string> $labels  all labels of the pricelist, code => name
     */
    private function labels(array $current, array $labels): \Ease\Html\DivTag
    {
        $box = new \Ease\Html\DivTag(null, ['class' => 'mb-3']);
        $box->addItem('<label class="form-label fw-semibold">'.self::e(_('Labels')).'</label>');
        $box->addItem('<div class="form-text mb-2">'.self::e(sprintf(
            _('Customers of ClientZone are offered the items labelled both %s and %s.'),
            PricelistChecker::LABEL_SHOP,
            PricelistChecker::LABEL_ACTIVE,
        )).'</div>');

        // A label the item has but the list lacks stays selectable, so saving does not drop it silently
        foreach ($current as $code) {
            if (!isset($labels[$code])) {
                $labels[$code] = $code;
            }
        }

        foreach ($labels as $code => $title) {
            $id = 'label_'.preg_replace('/\W/', '_', (string) $code);
            $steering = \in_array($code, PricelistItem::STEERING_LABELS, true);
            $box->addItem(
                '<div class="form-check form-check-inline">'
                .'<input class="form-check-input" type="checkbox" name="stitky[]" id="'.$id.'" value="'.self::e((string) $code).'"'.(\in_array($code, $current, true) ? ' checked' : '').'>'
                .'<label class="form-check-label" for="'.$id.'" title="'.self::e((string) $title).'">'
                .($steering ? '<strong class="text-primary">'.self::e((string) $code).'</strong>' : self::e((string) $code)).'</label>'
                .'</div>',
            );
        }

        return $box;
    }

    /**
     * Place in the pricelist tree: the section of the shop the item belongs to.
     */
    private function placement(PricelistTree $tree, ?int $node): \Ease\Html\DivTag
    {
        $options = [];

        foreach ($tree->flat() as $row) {
            $options[(string) $row['id']] = str_repeat("\u{00A0}\u{00A0}\u{00A0}", $row['depth']).$row['name'];
        }

        return self::field(
            _('Place in the pricelist tree'),
            'node',
            self::select('node', $options, $node !== null ? (string) $node : '', _('(not placed)')),
            _('ClientZone lists the items below its catalog node, so this decides which section of the shop the item is in'),
        );
    }

    /**
     * @param array<string, string> $attributes
     */
    private static function input(string $name, string $value, array $attributes = []): string
    {
        $extra = '';

        foreach ($attributes as $attribute => $content) {
            $extra .= ' '.$attribute.'="'.self::e($content).'"';
        }

        return '<input class="form-control" type="text" id="'.$name.'" name="'.$name.'" value="'.self::e($value).'"'.$extra.'>';
    }

    /**
     * @param array<int|string, string> $options value => caption
     * @param bool                  $withCode    prefix the caption with the value (AbraFlexi records are known by codes)
     */
    private static function select(string $name, array $options, string $current, ?string $empty = null, bool $withCode = false): string
    {
        $html = '<select class="form-select" id="'.$name.'" name="'.$name.'">';

        if ($empty !== null) {
            $html .= '<option value="">'.self::e($empty).'</option>';
        }

        if ($current !== '' && !isset($options[$current])) {
            $options[$current] = $current;
        }

        foreach ($options as $value => $caption) {
            $text = $withCode && $caption !== '' && $caption !== (string) $value ? $value.' — '.$caption : ($caption !== '' ? $caption : (string) $value);
            $html .= '<option value="'.self::e((string) $value).'"'.((string) $value === $current ? ' selected' : '').'>'.self::e($text).'</option>';
        }

        return $html.'</select>';
    }

    private static function field(string $caption, string $for, string $control, ?string $help = null): \Ease\Html\DivTag
    {
        return new \Ease\Html\DivTag(
            '<label class="form-label fw-semibold" for="'.$for.'">'.self::e($caption).'</label>'.$control
            .($help !== null ? '<div class="form-text">'.self::e($help).'</div>' : ''),
            ['class' => 'mb-3'],
        );
    }

    /**
     * Code of a "code:ABC" reference.
     */
    private static function codeOf(string $reference): string
    {
        return trim((string) preg_replace('/^code:/i', '', $reference));
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function value(array $values, string $column): string
    {
        $value = $values[$column] ?? '';

        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
