<?php

namespace AdminZone\whplugins;

/**
 * Description of FakturaPrijata
 *
 * @author vitex
 */
class FakturaPrijata extends \AdminZone\WebHookHandler
{
    use \AbraFlexi\stitky;
    use \AbraFlexi\firma;
    use \AbraFlexi\sum;
    use \AbraFlexi\subItems;

    /**
     * Order Data
     * @var array
     */
    public $orderData = null;

    /**
     *
     * @var string
     */
    public ?string $evidence = 'faktura-prijata';


    /**
     * Invoice was inserted. What to do now ?
     *
     * @return boolean Change was processed. Ok remeber it
     */
    public function create()
    {

        $this->addStatusMessage(sprintf(
            'New invoice %s was accepted',
            $this->getDataValue('kod')
        ) . ' ' . $this->getDataValue('firma')->showAs . ' Celkem: ' . $this->getDataValue('sumCelkem') . ' Zbyva uh.: ' . $this->getDataValue('zbyvaUhradit') . ' ' . $this->getDataValue('mena')->showAs);

        switch (\AbraFlexi\Functions::uncode(strval($this->getDataValue('typDokl')))) {
            case 'ZAVAZEK':
                $copyer = new \AbraFlexi\Bricks\Convertor(
                    $this,
                    new \AdminZone\Zavazek(['typDokl' => \AbraFlexi\Functions::code('OST-ZÁVAZKY'), 'stitky' => 'SYSTEM'])
                );

                $zavazek = $copyer->conversion();

                if ($zavazek->sync()) {
                    $zavazek->addStatusMessage(sprintf(
                        _('new commitment %s'),
                        $zavazek->getApiUrl()
                    ), 'success');
                    try {
                        $this->deleteFromAbraFlexi();
                        $this->addStatusMessage(sprintf('removig %s', $this), 'debug');
                    } catch (\AbraFlexi\Exception $ex) {
                        $this->addStatusMessage(sprintf('error removig %s', $this));
                    }
                } else {
                    $zavazek->addStatusMessage(sprintf(
                        _('error creating commitment from %s'),
                        $this
                    ), 'error');
                }

                break;

            case 'FAKTURA':
                if (!empty($this->getDataValue('kontaktEmail'))) {
                    $notify = $this->getDataValue('kontaktEmail');
                } else {
                    $adrHelper = new \AbraFlexi\Adresar();
                    $adrRaw = $adrHelper->getColumnsFromAbraFlexi(
                        ['email'],
                        ['kod' => \AbraFlexi\Functions::uncode($this->getDataValue('firma'))]
                    );
                    $notify = empty($adrRaw) ? '' : $adrRaw[0]['email'];
                    unset($adrHelper);
                }

                if (!empty($notify)) {
//                    if (!strstr($this->getDataValue('stitky'), 'SETTLE_NOTIFIED')) {

                    if ($this->kumpan()) {
                        $this->setDataValue('email', $notify);
                        $potvrzovac = new \AdminZone\PotvrzeniPrijetiFaktury($this);
                        if ($potvrzovac->send()) {
//                            $this->insertToAbraFlexi(['id' => $this->getRecordID(),
//                                'stitky' => 'INVOICE_ACCEPT_NOTIFIED']);
                        }
                    }
                }
                break;

            default:
                $this->addStatusMessage('Unknown invoice type ' . \AbraFlexi\Functions::uncode(strval($this->getDataValue('typDokl'))), 'warning');
                break;
        }

        return true;
    }

    /**
     * Is invoice issuer meber of inner circle ?
     *
     * @return boolean membership
     */
    public function kumpan()
    {
        $provider = \AbraFlexi\Functions::uncode($this->getDataValue('firma'));
        switch ($provider) {
            case 'VITEX':
            case 'LOTEK':
            case 'LUKASMALKOVSKY':
            case 'HANY':
            case 'CHARVÁT':
            case 'HANKO':
            case 'XCHAOS':
                $result = true;
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

    /**
     * Is invoice Settled
     *
     * @return type
     */
    public function bankOrderSent()
    {
        $changes = $this->getChanges();
        return isset($changes['stavUzivK']) && ($changes['datUhr'] == 'stavUziv.celPrikaz') ? true : false;
    }

//    /**
//     * Invoice was updated. What to do now ?
//     *
//     * @return boolean Change was processed. Ok remeber it
//     */
//    public function update()
//    {
//        if ($this->bankOrderSent()) {
//            $this->addStatusMessage(sprintf('Bank order to settle Invoice %s was sent',
//                    $this->getDataValue('kod')));
//
//            if (\AbraFlexi\Functions::uncode($this->getDataValue('typDokl')) == 'FAKTURA') {
//
//                if (!empty($this->getDataValue('kontaktEmail'))) {
//                    $notify = $this->getDataValue('kontaktEmail');
//                } else {
//                    $adrHelper = new \AbraFlexi\Adresar($this->getDataValue('firma'));
//                    $notify    = \AdminZone\Mailer::getNotificationEmailAddres($adrHelper);
//                    unset($adrHelper);
//                }
//
//                if (!empty($notify) && $this->kumpan()) {
////                    if (!strstr($this->getDataValue('stitky'), 'SETTLE_NOTIFIED')) {
//                    $this->setDataValue('email', $notify);
//                    $potvrzovac = new \AdminZone\PotvrzeniOdeslaniUhrady($this);
//                    if ($potvrzovac->send()) {
////                            $this->insertToAbraFlexi(['id' => $this->getRecordID(),
////                                'stitky' => 'SETTLE_NOTIFIED']);
////                        }
//                    }
//                } else {
//                    $this->addStatusMessage(
//                        sprintf(_('No notify email for %s  %s'),
//                            \AbraFlexi\Functions::uncode($this->getDataValue('firma')),
//                            \AdminZone\ParovacFaktur::apiUrlToLink($this->apiURL)
//                        )
//                        , 'warning');
//                }
//            }
//        }
//        return true;
//    }

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
