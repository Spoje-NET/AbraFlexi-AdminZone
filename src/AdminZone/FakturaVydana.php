<?php

/**
 * AdminZone - Faktura vydana
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017-2022 Spoje.Net
 */

namespace AdminZone;

/**
 * Description of Engine
 *
 * @author vitex
 */
class FakturaVydana extends \AbraFlexi\FakturaVydana
{
    /**
     * Do not require SQL
     * @var null
     */
    public $myTable = null;

    /**
     * AbraFlexi Invoice
     *
     * @param array $options Connection settings override
     */
    public function __construct($options = [])
    {
        parent::__construct();
        $this->setDataValue(
            'typDokl',
            self::code(\Ease\Shared::cfg('ABRAFLEXI_TYP_FAKTURY'))
        );
    }

    /**
     * Remove Initial and Ending " from string
     *
     * @param string $string
     *
     * @return string
     */
    public static function stripComas($string)
    {
        return substr($string, 1, -1);
    }

    /**
     * Přidá položku do objednávky
     *
     * @param int|string $orderID
     * @param array      $itemData
     *
     * @return boolean result
     */
    public function addItemToOrder($orderID, $itemData)
    {
        $this->dataReset();
        $this->setMyKey($orderID);
        $itemData['typDokl'] = $this->configuration['kodvydaneobjednavky'];
        $this->addArrayToBranch($itemData, 'polozkyDokladu');
        $this->insertToAbraFlexi();
        return $this->lastResponseCode == 201;
    }

    /**
     *
     * @param array $timeEntriesRaw
     */
    public function takeItemsFromArray($timeEntriesRaw)
    {
        $itemsData = [];
        foreach ($timeEntriesRaw as $rowId => $timeEntryRaw) {
            $timeEntries[$timeEntryRaw['project']][$rowId] = $timeEntryRaw;
        }

        foreach ($timeEntries as $projectName => $projectTimeEntries) {
            foreach ($projectTimeEntries as $rowId => $timeEntry) {
                $nazev = $timeEntry['issue'];

                if (isset($itemsData[$projectName][$nazev])) {
                    $itemsData[$projectName][$nazev]['mnozMj'] += floatval($timeEntry['hours']);
                } else {
                    $itemsData[$projectName][$nazev] = [
                        'typPolozkyK' => 'typPolozky.katalog',
                        'poznam' => $timeEntry['comments'],
                        'nazev' => $nazev,
                        'mnozMj' => floatval($timeEntry['hours']),
                        'cenik' => self::code(\Ease\Shared::cfg('ABRAFLEXI_CENIK'))];
                }
            }
        }

        foreach ($itemsData as $projectName => $projectData) {
            $this->dataReset();
            $this->addArrayToBranch(
                ['typPolozkyK' => 'typPolozky.text', 'nazev' => 'Projekt: ' . $projectName],
                'polozkyFaktury'
            );

            foreach ($projectData as $taskName => $taskData) {
                $this->addArrayToBranch($taskData, 'polozkyFaktury');
            }

            $this->insertToAbraFlexi();
            $this->logOperationResult($this->getMyKey(), $projectName);
        }
    }

    /**
     * Loguje info o výsledku operace importu.
     *
     * @param string $id ID záznamu
     * @param string $caption
     */
    function logOperationResult($id, $caption)
    {
        if ($this->lastResponseCode == 201) {
            if ($this->lastResult['stats']['created'] == 1) {
                $operation = 'Insert';
                $importStatus = 'success';
            } else {
                $operation = 'Update';
                $importStatus = 'warning';
            }
        } else {
            $importStatus = 'error';
            if ($this->recordExists(['id' => $id])) {
                $operation = 'Update';
            } else {
                $operation = 'Insert';
            }
        }
        $this->addStatusMessage($operation . ' ' . $id . ' ' . $caption, $importStatus);
    }

    /**
     * Which proformas turn to invoices ?
     *
     * @param string $code
     *
     * @return array
     */
    public function getThisMonthIDs($code = 'FAKTURA')
    {

        $result = [];
        $this->defaultUrlParams['order'] = 'datVyst@A';
        $invoices = $this->getColumnsFromAbraFlexi(
            [
            'id',
            'kod',
            'stavUhrK',
            'firma',
            'buc',
            'varSym',
            'specSym',
            'sumCelkem',
            'duzpPuv',
            'typDokl',
            'datVyst'],
            ["(stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr') AND storno eq false AND typDokl eq '" . \AbraFlexi\Functions::code($code) . "'"],
            'id'
        );

        if ($this->lastResponseCode == 200) {
            $result = $invoices;
        }
        return $result;
    }

    /**
     * Vrací neuhrazene faktury
     *
     * @return array
     */
    public function getAllDebts()
    {
        $result = [];
        $this->defaultUrlParams['order'] = 'datVyst@A';
        $invoices = $this->getColumnsFromAbraFlexi(
            [
            'id',
            'kod',
            'stavUhrK',
            'firma',
            'buc',
            'varSym',
            'specSym',
            'sumCelkem',
            'duzpPuv',
            'typDokl',
            'datSplat',
            'mena',
            'zamekK',
            'email',
            'stavMailK',
            'stitky',
            'datVyst'],
            ["(stavUhrK is null OR stavUhrK eq 'stavUhr.castUhr') AND storno eq false"],
            'id'
        );

        if ($this->lastResponseCode == 200) {
            $result = $invoices;
        }
        return $result;
    }
}
