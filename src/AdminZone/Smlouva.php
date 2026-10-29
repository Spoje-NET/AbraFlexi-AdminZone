<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone;

/**
 * Description of Smlouva
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class Smlouva extends \AbraFlexi\Smlouva
{
    /**
     *
     * @param array $conditions
     *
     * @return array
     */
    public function getContracts($conditions = [])
    {
        return $this->getColumnsFromAbraFlexi(
            ['kod', 'typDoklFak', 'polozkySmlouvy(id,kod,autoGen,cenik(nazev),smlouva)'],
            array_merge(['relations' => 'polozkySmlouvy'], $conditions),
            'kod'
        );
    }

    public function getDeclinedContracts($conditions = [])
    {
        return $this->getContracts(array_merge(
            ['stavSml' => 'code:VYPOVED'],
            $conditions
        ));
    }

    public function getInconsistentDeclined()
    {
        $inconsistentDeclined = [];
        foreach ($this->getDeclinedContracts() as $contractInfo) {
            if (array_key_exists('polozkySmlouvy', $contractInfo)) {
                $allDisabled = true;
                foreach ($contractInfo['polozkySmlouvy'] as $item) {
                    if ($item['autoGen'] == 'true') {
                        $inconsistentDeclined[$item['id']] = $item;
                    }
                }
            }
        }
        return $inconsistentDeclined;
    }

    public function fixInconsistentDeclined()
    {
        foreach ($this->getInconsistentDeclined() as $contractItemInfo) {
            $this->disableItemsInContract($contractItemInfo);
            $this->addStatusMessage(
                sprintf(
                    _('fixing contract\'s %s item %s generate flag to disabled'),
                    $contractItemInfo['smlouva'],
                    $contractItemInfo['kod']
                ),
                $this->disableItemsInContract($contractItemInfo) ? 'success' : 'error'
            );
        }
    }

    public function disableItemsInContract($contractItemInfo)
    {
        $fixer = new \AbraFlexi\RW(null, ['evidence' => 'smlouva-polozka']);
        $fixer->insertToAbraFlexi(['id' => (int) $contractItemInfo['id'], 'autoGen' => false]);
        return ($fixer->lastResponseCode == 201);
    }
}
