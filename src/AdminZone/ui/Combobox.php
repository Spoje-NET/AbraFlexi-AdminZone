<?php

/**
 * AdminZone - FuelUX Combobox.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone\ui;

class Combobox extends \Ease\Html\DivTag
{
    /**
     * Jméno vstupního prvku.
     *
     * @var string
     */
    public $name = null;

    /**
     * @var type
     */
    public $value = null;

    /**
     * @var type
     */
    public $items = [];

    /**
     * Vstupní políčko.
     *
     * @var \Ease\Html\InputTextTag
     */
    public $input = null;

    /**
     * FuelUX ComboBox.
     *
     * @param string $name
     * @param string $value
     * @param array  $items
     */
    public function __construct($name, $value = null, array $items = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->items = $items;
        $properties = [
            'class' => 'input-group input-append dropdown combobox',
            'data-initialize' => 'combobox',
        ];
        parent::__construct(null, $properties);
        $this->input = $this->addItem(new \Ease\Html\InputTextTag(
            $this->name,
            $this->value,
            ['class' => 'form-control']
        ));
    }

    public function finalize(): void
    {
        \AdminZone\ui\WebPage::singleton()->includeCss(
            'twitter-bootstrap/css/fuelux.css',
            true
        );
        \AdminZone\ui\WebPage::singleton()->includeJavascript('/javascript/twitter-bootstrap/fuelux.js');

        $dropDown = $this->addItem(
            new \Ease\Html\DivTag(
                new \Ease\Html\ButtonTag(
                    new \Ease\Html\Span(null, ['class' => 'caret']),
                    ['type' => 'button', 'class' => 'btn btn-default dropdown-Kimaie', 'data-Kimaie' => 'dropdown']
                ),
                ['class' => 'input-group-btn']
            )
        );

        $options = $dropDown->addItem(new \Ease\Html\UlTag(
            null,
            ['class' => 'dropdown-menu dropdown-menu-right']
        ));

        if ($this->items) {
            foreach ($this->items as $itemID => $itemContent) {
                $options->addItem(new \Ease\Html\LiTag(new \Ease\Html\ATag(
                    '#',
                    $itemContent
                ), ['data-value' => $itemID]));
            }
        }
    }
}
