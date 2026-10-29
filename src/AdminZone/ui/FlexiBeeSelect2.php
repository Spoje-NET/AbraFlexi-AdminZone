<?php

namespace AdminZone\ui;

/**
 * Description of AbraFlexiSelect2
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class AbraFlexiSelect2 extends \Ease\Html\InputTextTag
{
    public $candidates = [];

    /**
     *
     *
     * @param AbraFlexi\RO $engine        AbraFlexi object
     * @param array                 $conds         Which candidates we want
     * @param array                 $tagProperties Additional Input tag properties
     */
    public function __construct($engine, $conds = [], $tagProperties = array())
    {
        parent::__construct(
            $engine->getEvidence(),
            $engine->getMyKey(),
            $tagProperties
        );
        $this->candidates = $engine->getColumnsFromAbraFlexi(['nazev'], $conds);
    }

    public function candidates2json()
    {
        $cdd = [];
        foreach ($this->candidates as $pos => $itemData) {
            $cdd[] = '{ id: ' . $itemData['id'] . ', text: \'' . addslashes($itemData['nazev']) . "'}";
        }
        return '[' . implode(",\n", $cdd) . ']';
    }

    public function finalize(): void
    {
        $this->includeCss('css/select2.css');
        $this->includeJavaScript('js/select2.full.js');
        $this->addJavaScript('
var ' . $this->getTagID() . ' = ' . $this->candidates2json() . ';

$("#' . $this->getTagID() . '").select2({
  data: ' . $this->getTagID() . '
});
');
        parent::finalize();
    }
}
