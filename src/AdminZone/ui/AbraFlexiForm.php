<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\ui;

/**
 * Description of AbraFlexiForm
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class AbraFlexiForm extends \Ease\TWB\Form
{
    /**
     *
     * @var AbraFlexi\RO
     */
    public $engine = null;
    private $propCache = [];

    public function __construct(
        $engine,
        $formAction = null,
        $formMethod = 'post',
        $formContents = null,
        $tagProperties = null
    ) {
        $this->engine = $engine;
        parent::__construct(
            $engine->getEvidence() . '' . $engine,
            $formAction,
            $formMethod,
            $formContents,
            $tagProperties
        );
    }

    public function getColumnProperties($column)
    {
        if (array_key_exists('column', $this->propCache)) {
            $columnProperties = $this->propCache[$column];
        } else {
            $columnProperties = $this->propCache[$column] = $this->engine->getColumnInfo($column);
        }
        return $columnProperties;
    }

    /**
     * Vloží prvek do formuláře.
     *
     * @param mixed  $input       AbraFlexi column name or Input Widget
     * @param string $caption     Popisek
     * @param string $placeholder předvysvětlující text
     * @param string $helptext    Dodatečná nápověda
     */
    public function addInput(
        $input,
        $caption = null,
        $placeholder = null,
        $helptext = null
    ) {
        if (is_string($input)) {
            $caption = $this->getColumnCaption($input);
            $input = $this->columnWidget($input);
            $placeholder = $this->engine->getDataValue($input);
        }
        return parent::addInput($input, $caption, $placeholder, $helptext);
    }

    public function getColumnCaption($colName)
    {
        return $this->getColumnProperties($colName)['name'];
    }

    public function columnWidget($columnName)
    {
        $colProperties = $this->getColumnProperties($columnName);
        $type = $colProperties['type'];
        $name = $colProperties['name'];
        $propertyName = isset($colProperties['propertyName']) ? $colProperties['propertyName'] : $colProperties['name'];
        $value = $this->engine->getDataValue($propertyName);

        $inputProperties = [];

        if (($type != 'logic') && ($type != 'date')) {
            $inputProperties['OnChange'] = $this->onChangeCode($propertyName);
        }

        if (isset($colProperties['mandatory']) && ($colProperties['mandatory'] === 'true')) {
            $inputProperties[] = 'required';
        }
        if (isset($colProperties['isWritable']) && ($colProperties['isWritable'] === 'false')) {
            $inputProperties[] = 'disabled';
        }


        switch ($type) {
            case 'numeric':
                $inputProperties['pattern'] = '^\d+(\.|\,)\d{2}$';
                $widget = new \Ease\Html\InputNumberTag(
                    $propertyName,
                    $value,
                    $inputProperties
                );
                break;
            case 'integer':
                $widget = new \Ease\Html\InputNumberTag(
                    $propertyName,
                    $value,
                    $inputProperties
                );
                break;
            case 'logic':
                $widget = new YesNoSwitch(
                    $propertyName,
                    $value,
                    true,
                    $inputProperties
                );
                break;
            case 'relation':
                $evidence = '';
                if (isset($colProperties['url'])) {
                    $tmp = explode('/', $colProperties['url']);
                    $evidence = end($tmp);
                }
                $colProperties['data-evidence'] = $evidence;
                $widget = new RelationSelect(
                    $propertyName,
                    $value,
                    $inputProperties
                );

                break;

            case 'select':
                $widget = new \Ease\Html\Select(
                    $propertyName,
                    $this->colValues($colProperties),
                    $value,
                    null,
                    $inputProperties
                );
                break;
            case 'date':
                $inputProperties['data-format'] = 'YYYY-MM-DD';
                $widget = new DateTimePicker(
                    $propertyName,
                    $value,
                    $inputProperties,
                    $this->onChangeCode($propertyName)
                );
                break;
            case 'datetime':
                $inputProperties['data-format'] = 'YYYY-MM-DDTHH:mm:ss';
                $widget = new \Ease\Html\InputDateTimeLocalTag(
                    $propertyName,
                    $value,
                    $inputProperties,
                    $this->onChangeCode($propertyName)
                );
                break;
            case 'string':
                $widget = new \Ease\Html\InputTextTag(
                    $propertyName,
                    $value,
                    $inputProperties
                );
                break;
            default:
                $this->addStatusMessage(sprintf(
                    _('Unknown type of data %s'),
                    $type
                ), 'warning');
                $widget = new \Ease\Html\InputTag(
                    $propertyName,
                    $value,
                    $inputProperties
                );
                break;
        }

        return $widget;
    }

    /**
     * Vrací pole možností pro select
     *
     * @param array $colProperties
     * @return array
     */
    private function colValues($colProperties)
    {
        $options = [];
        if (isset($colProperties['values'])) {
            foreach ($colProperties['values']['value'] as $colValue) {
                $options[$colValue['@key']] = $colValue['$'];
            }
        }
        return $options;
    }

    /**
     * Vraci kod pro ukladani policka formulare po editaci
     *
     * @param string $fieldName
     *
     * @return string javascript
     */
    public function onChangeCode($fieldName)
    {
        $chCode = '';
        $id = $this->engine->getMyKey();
        if (!is_null($id)) {
            $chCode = 'saveColumnData(\'' . str_replace(
                '\\',
                '-',
                get_class($this->engine)
            ) . '\', \'' . $id . '\', \'' . $fieldName . '\', \'' . $this->engine->getEvidence() . '\')';
        }
        return $chCode;
    }
}
