<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdminZone\whplugins;

/**
 * Description of FakturaVydana
 *
 * @author vitex
 */
class FakturaVydana extends \AdminZone\WebHookHandler
{
    use \AbraFlexi\stitky;
    use \AbraFlexi\firma;
    use \AbraFlexi\sum;
    use \AbraFlexi\subItems;

    /**
     * Database table name
     * @var string
     */

    /**
     * Order Data
     * @var array
     */
    public $orderData = null;

    /**
     * Evidence used
     * @var string
     */
    public ?string $evidence = 'faktura-vydana';


    /**
     * Is invoice Settled
     *
     * @return boolean
     */
    public function isSettled()
    {
        $changes = $this->getChanges();
        return array_key_exists('stavUhrK', $changes) && $changes['stavUhrK']['new'] == 'stavUhr.uhrazeno';
    }

    /**
     * Obtain Product codes used in this invoice
     *
     * @return array
     */
    public function getProdcodes()
    {
        $prodCodes = [];
        $orderItems = $this->getDataValue('polozkyFaktury');
        if (!empty($orderItems) && count($orderItems)) {
            foreach ($orderItems as $orderItem) {
                if (!empty($orderItem['cenik'])) {
                    $prodCodes[$orderItem['cenik']] = $orderItem['cenik'];
                }
            }
        }
        return $prodCodes;
    }

    /**
     * Obtain instanced modules for invoice items
     *
     * @return \AdminZone\OrderPlugin
     */
    public function orderModulesForInvoiceItems()
    {
        $orderModules = null;
        $modulesForProducts = $this->getModulesForProducts($this->getProdcodes());
        foreach ($modulesForProducts as $pluginName) {
            $className = '\\AdminZone\\orderplugins\\' . $pluginName;
            $orderModules[$pluginName] = new $className($modulesForProducts);
        }
        return $orderModules;
    }

    /**
     *
     * @param array $prodCodes
     *
     * @return array
     */
    public function getModulesForProducts($prodCodes)
    {
        $processors = [];
        foreach ($prodCodes as $prodCode) {
            $processor = self::getProcessorForProduct(\AbraFlexi\Functions::code($prodCode));
            if (!empty($processor)) {
                $processors[$prodCode] = $processor;
            }
        }
        return $processors;
    }

    /**
     *
     * @param sting $product AbraFlexi product CODE
     *
     * @return string|null
     */
    public static function getProcessorForProduct($product)
    {
        $atributor = new \AbraFlexi\RW(null, ['evidence' => 'atribut']);
        $atribut = $atributor->getColumnsFromAbraFlexi(
            ['id', 'valString'],
            ['cenik' => $product, 'typAtributu' => 'code:API']
        );
        return empty($atribut) ? null : $atribut[0]['valString'];
    }

    /**
     *
     */
    public function create()
    {
        $this->addStatusMessage(sprintf(
            'New invoice %s %s was created',
            $this->getDataValue('typDokl'),
            $this->getDataValue('kod')
        ) . ' ' .
                $this->getDataValue('firma')->showAs . ' ' .
                $this->getDataValue('sumCelkem') . ' ' .
                $this->getDataValue('mena')->showAs);
    }

    /**
     * Invoice was updated. What to do now ?
     *
     * @return boolean Change was processed. Ok remeber it
     */
    public function update()
    {
        if ($this->isSettled()) {
            $this->addStatusMessage(sprintf(
                'Processing settled invoice %s ',
                $this->getDataValue('kod')
            ));
            $orderModules = $this->orderModulesForInvoiceItems();
            if (!empty($orderModules) && count($orderModules)) {
                $this->loadOrderData();
                foreach ($orderModules as $orderModule) {
                    $this->addStatusMessage(sprintf(
                        _('Settle Item(s) %s by %s'),
                        implode(',', $orderModule->getMyProductCodes()),
                        str_replace('\\', '/', get_class($orderModule))
                    ));
                    $orderModule->settled($this);
                }
            }

//            if (\AbraFlexi\Functions::uncode($this->getDataValue('typDokl')) == 'FAKTURA') {
            $adrHelper = new \AbraFlexi\Adresar($this->getDataValue('firma'));

            if (!empty($this->getDataValue('kontaktEmail'))) {
                $notify = $this->getDataValue('kontaktEmail');
            } else {
                $notify = \AdminZone\Mailer::getNotificationEmailAddres($adrHelper);
            }

            $engine = new \AdminZone\Upominac();
            $zewlScore = $engine->getCustomerScore($adrHelper->getMyKey());

            if ($zewlScore == 0) {
                $this->addStatusMessage(
                    $adrHelper->getDataValue('nazev') . ' ' . $adrHelper->getDataValue('stitky') . ' SCORE: ' . $zewlScore,
                    $engine->enableCustomer($adrHelper->getData()) ? 'success' : 'error'
                );
            } else {
                $this->addStatusMessage('Nevolam reset dashboards zewl: ' . $adrHelper->getRecordCode() . ' / ' . $zewlScore, 'debug');
                $this->addStatusMessage(
                    $adrHelper->getDataValue('nazev') . ' ' . $adrHelper->getDataValue('stitky') . ' SCORE: ' . $zewlScore,
                    'warning'
                );
            }



            if (!strstr($this->getDataValue('stitky'), 'SETTLE_NOTIFIED')) {
                $this->setDataValue('email', $notify);
                $potvrzovac = new \AdminZone\PotvrzeniPrijetiUhrady($this);
                try {
                    if ($potvrzovac->send()) {
                        $this->insertToAbraFlexi(['id' => $this->getRecordIdent(), 'stitky' => 'SETTLE_NOTIFIED']);
                    }
                } catch (\AbraFlexi\Exception $exc) {
                    $this->addStatusMessage($exc->getMessage(), 'error');
                }
            }

//            }
        }
        return true;
    }

    /**
     * take deata from attachment order.json
     */
    public function loadOrderData()
    {
        $attachments = \AbraFlexi\Priloha::getAttachmentsList($this);
        foreach ($attachments as $attachment) {
            if ($attachment['nazSoub'] == 'order.json') {
                $orderJson = \AbraFlexi\Priloha::getAttachment($attachment['id']);
                $this->orderData = json_decode($orderJson, true)[0];
            }
        }
    }
}
