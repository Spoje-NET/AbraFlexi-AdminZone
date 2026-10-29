<?php

/**
 * AdminZone - Třída pro zobrazení Smluv ke Generaci.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015 Spoje.Net
 */

namespace AdminZone;

/**
 * Description of ZalohyZeSmluvDoZavazku
 *
 * @author vitex
 */
class SmlouvyGenerujici extends \AbraFlexi\DodavatelskaSmlouva
{
    /**
     * Vygeneruj faktury ze smluv
     */
    public function smlouvy()
    {
        return $this->getColumnsFromAbraFlexi(
            ['id', 'kod', 'nazev', 'popis'],
            ['stitky' => 'code:GENERUJ'],
            'id'
        );
    }

    /**
     * Proved reset posledního data generování faktur ze smlouvy, tak aby mohly být generovány znovu
     *
     * @param int $sid ID Smlouvy
     */
    public function reset($sid)
    {
        $this->loadFromAbraFlexi((int) $sid);
        $polozkySmlouvyInt = $this->getDataValue('polozkySmlouvy');

        foreach ($polozkySmlouvyInt as $agreementContent) {
            $itemId = $agreementContent['id'];
            $updateData = ['id' => $sid];

            $regenerateDay = $this->getDataValue('den');
            $regenerateMonth = $this->getDataValue('mesic');
            if (strlen($regenerateMonth)) {
                $regTime = date('Y') . '-' . $regenerateMonth . '-' . $regenerateDay;
            } else {
                $regTime = date('Y-m') . '-' . $regenerateDay;
            }
            $datPoslFaktObd = \AbraFlexi\RW::timestampToFlexiDate(strtotime("$regTime -1 days"));
            $updateData['polozkySmlouvy'][] = ['smlouva-polozka' => ['id' => $itemId,
                    'datPoslFaktObd' => $datPoslFaktObd, 'poznam' => sprintf(
                        _('Resetnul %s v %s na %s'),
                        \Ease\Shared::user()->getUserName(),
                        \AbraFlexi\RW::timestampToFlexiDate(time()),
                        $datPoslFaktObd
                    )]];

            $this->insertToAbraFlexi($updateData);
            if ($this->lastResponseCode === 201) {
                $this->addStatusMessage(
                    sprintf(
                        _('Datum posledniho generovani faktur (%s #%d) nastaven na (%s)'),
                        $this->getDataValue('nazev'),
                        $itemId,
                        $datPoslFaktObd
                    ),
                    'success'
                );
            } else {
                $this->addStatusMessage(
                    sprintf(
                        _('Nepovedlo se nastavení Data posledniho generovani faktur (%s #%d) na včerejšek'),
                        $this->getDataValue('nazev'),
                        $itemId,
                        $datPoslFaktObd
                    ),
                    'warning'
                );
            }
        }
    }

    /**
     * Vrací tabulku smluv s odkazy na refresh
     *
     * @param string $evidence dodavatelska-smlouva|smlouva
     * @return \Ease\Html\TableTag
     */
    public function contractTable($evidence = null)
    {
        if (!is_null($evidence)) {
            $this->setEvidence($evidence);
        }
        $contractTable = new \Ease\Html\TableTag(null, ['class' => 'table']);
        $contractTable->addRowHeaderColumns([_('id'), _('kód'), _('název'), _('popis'),
            _('Reset')]);

        foreach ($this->smlouvy() as $smlouva) {
            $smlouva['nazev'] = new \Ease\Html\ATag(
                $this->getEvidenceURL() . '/' . $smlouva['id'],
                $smlouva['nazev']
            );
            $smlouva[] = new \Ease\TWB\LinkButton(
                '?evidence=' . $this->getEvidence() . '&reset=' . $smlouva['id'],
                \Ease\TWB\Part::GlyphIcon('refresh'),
                'warning'
            );
            $contractTable->addRowColumns($smlouva);
        }
        return $contractTable;
    }
}
