<?php

/**
 * AdminZone - Formulář adresy/firmy.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */

namespace AdminZone\ui;

class AddressForm extends \Ease\TWB\Form
{
    /**
     * Otázky.
     *
     * @var type
     */
    public $address = null;

    /**
     * Formulář Bootstrapu.
     *
     * @param \AbraFlexi\Adresar $address       jméno formuláře
     * @param string              $formAction    cíl formulář např login.php
     * @param string              $formMethod    metoda odesílání POST|GET
     * @param mixed               $formContents  prvky uvnitř formuláře
     * @param array               $tagProperties vlastnosti tagu například:
     *                              array('enctype' => 'multipart/form-data')
     */
    public function __construct(
        $address,
        $formAction = null,
        $formMethod = 'post',
        $formContents = null,
        $tagProperties = null
    ) {
        $addressID = $address->getMyKey();
        $this->address = $address;
        parent::__construct(['name' => 'address' . $addressID, 'action' => $formAction, 'method' => $formMethod], $formContents, $tagProperties);

        $this->addInput(new \Ease\Html\InputTag(
            'kod',
            $address->getDataValue('kod')
        ), _('Code'));
        $this->addInput(new \Ease\Html\InputTag(
            'nazev',
            $address->getDataValue('nazev')
        ), _('Name'));

        if (strlen($address->getDataValue('email')) == 0) {
            $address->addStatusMessage(_('Email address is empty'), 'warning');
        }

        $this->addInput(new \Ease\Html\InputTag(
            'email',
            $address->getDataValue('email')
        ), _('Email'));

        $this->addInput(new \Ease\Html\TextareaTag(
            'poznam',
            $address->getDataValue('poznam')
        ), _('Note'));

        $this->addItem(new \Ease\Html\InputHiddenTag(
            'class',
            get_class($address)
        ));
//        $this->addItem(new \Ease\Html\InputHiddenTag('enquiry_id', $address->getDataValue('enquiry_id')));

        if (!is_null($addressID)) {
            $this->addItem(new \Ease\Html\InputHiddenTag(
                $address->keyColumn,
                $addressID
            ));
        }
    }

    public function finalize(): void
    {
        $this->addItem(new \Ease\Html\DivTag(new \Ease\TWB\SubmitButton(
            _('Save'),
            'success'
        ), ['style' => 'text-align: right']));

        parent::finalize();
    }
}
