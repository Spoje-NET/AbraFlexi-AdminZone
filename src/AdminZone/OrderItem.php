<?php

/**
 * AdminZone - Order item.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2024 Spoje.Net
 */

namespace AdminZone;

use AbraFlexi\Bricks\Customer;

/**
 * Description of Order
 *
 * @author vitex
 */
class OrderItem extends AbraFlexiEngine {

    /**
     * Customer
     * @var Customer
     */
    public $customer;

    /**
     * PriceList
     * @var \AbraFlexi\Cenik
     */
    public $cenik = null;

    /**
     * Spoje.Net Customer ID
     * @var int
     */
    /**
     * Currency Amount
     * @var float
     */
    public $credit = null;

    /**
     * Service AbraFlexi Code
     * @var string
     */
    public $service = null;

    /**
     * Domain Name or Phone Number
     * @var string
     */
    public $detail = null;

    /**
     * Array of Form Plugins
     * @var array
     */
    public $plugins = [];

    /**
     * Order Class
     * @param array $formdata
     */
    public function __construct($formdata = array()) {
        parent::__construct();
        $this->takeData($formdata);
        $this->customer = new Customer();

        try {
            $this->cenik = new \AbraFlexi\Cenik(array_key_exists('productCode', $formdata) ? \AbraFlexi\Code::ensure($formdata['productCode']) : null);
        } catch (\AbraFlexi\Exception $exc) {
            $this->addStatusMessage($exc->getMessage(), 'error');
            $this->cenik = new \AbraFlexi\Cenik();
        }

        $this->loadPlugins();
    }

    /**
     * Data to use in object
     *
     * @param array $data
     * @return int
     */
    public function takeData(array $data): int {
        return parent::takeData($data);
    }

    /**
     * Load Order Plugins
     *
     * @return int Loaded plugins count
     */
    public function loadPlugins() {
        $this->plugins = self::findPlugins();
        return count($this->plugins);
    }

    /**
     *
     * @return \AdminZone\OrderPlugin[]
     */
    public static function findPlugins() {
        $plugins = [];
        $d = dir(__DIR__ . "/orderplugins");
        while (false !== ($entry = $d->read())) {
            if ($entry[0] == '.') {
                continue;
            }
            $pluginName = str_replace('.php', '', $entry);
            $className = '\\AdminZone\\orderplugins\\' . $pluginName;

            /**
             * @var \AdminZone\OrderPlugin OrderPlugin
             */
            $plugins[$pluginName] = new $className();
//            $plugins[$pluginName]->easeShared = $this->easeShared;
        }
        $d->close();
        return $plugins;
    }

    /**
     * Check order input
     *
     * @return boolean
     */
    public function checkInput() {
        return $this->checkService($this->getDataValue('service')) && $this->checkByPlugin();
    }

    /**
     * Check if service/product is chosen
     *
     * @param string $service
     * 
     * @return boolean
     */
    public function checkService($service) {
        if ($service == 'Common') {
            $ok = true;
        } else {
            $ok = false;
            if (empty($service) || !array_key_exists($service,$this->plugins)) {
                $this->addStatusMessage(_('Please choose service'), 'info');
            } else {
                $code = ($service == 'Pricelist') ? $this->getDataValue('kod') : urlencode($this->plugins[$service]->productCode);
                if (!is_null($code) && !empty(trim($code))) {
                    $this->cenik->ignore404(true);
                    $this->cenik->loadFromAbraFlexi(\AbraFlexi\Functions::code($code));
                    if ($this->cenik->lastResponseCode == 200) {
//                $this->addStatusMessage($this->cenik->getDataValue('nazev'),
//                    'success');
                        $ok = true;
                    } else {
                        $this->addStatusMessage(sprintf(
                                        _('Product with code %s does not exist'),
                                        $code
                                ), 'warning');
                        $ok = false;
                    }
                    $this->cenik->ignore404(false);
                } else {
                    $this->addStatusMessage(sprintf(
                                    _('Missing product code'),
                                    $code
                            ), 'warning');
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    /**
     * Check requirements for form chosen Plugin\
     *
     * @return boolean
     */
    public function checkByPlugin() {
        return $this->getServicePlugin()->controlFields($this);
    }

    /**
     * Obtain Current Service plugin object
     *
     * @return OrderPlugin
     */
    public function getServicePlugin() {
        $servicePlugin = null;
        $service = $this->getDataValue('service');
        if (isset($this->plugins[$service]) && is_object($this->plugins[$service])) {
            $servicePlugin = $this->plugins[$service];
        }
        return $servicePlugin;
    }

    /**
     * Make proforma for customer
     *
     * @param float $credit
     * @param string $service
     *
     * @return \AbraFlexi\FakturaVydana
     */
    public function makeProforma() {
        $polozka = $this->getServicePlugin()->processFields($this);

        $proforma = new \AbraFlexi\FakturaVydana();

        $today = date('Y-m-d');
        $proforma->setDataValue('firma', $this->customer->getAdresar());
        $proforma->setDataValue('typDokl', 'code:ZALOHA');
        $proforma->setDataValue(
                'uvodTxt',
                $this->cenik->getDataValue('nazev') . ' ' . $polozka['poznam']
        );
        $proforma->setDataValue('zavTxt', '');
        $proforma->setDataValue('poznam', 'WebForm');
        $proforma->setDataValue('duzpPuv', $today);
        $proforma->setDataValue('duzpUcto', $today);
        $proforma->setDataValue('datUcto', $today);
        $proforma->setDataValue('stitky', ['SYSTEM', 'API']);
        $proforma->addArrayToBranch($polozka, 'polozkyFaktury');
        //$proforma->debug = true;
        $proforma->insertToAbraFlexi();

        if (
                \AbraFlexi\Priloha::addAttachment(
                        $proforma,
                        'order.json',
                        json_encode(array_merge(
                                        $polozka,
                                        ['service' => $this->getDataValue('service')]
                                )),
                        'application/json'
                ) != 201
        ) {
            $proforma->addStatusMessage(
                    'Saving Order Details ' . $proforma->getAbraFlexiURL() . ' Failed',
                    'error'
            );
        }

        return $proforma;
    }

    /**
     * Add Item to cart
     *
     * @param int $itemId
     */
    public function addToCart($itemId = null) {
        $polozka = $this->getServicePlugin()->processFields($this);
        $polozka['service'] = $this->getDataValue('service');
        if (array_key_exists('cenik', $polozka)) {
            $polozka['icon'] = $this->cenik->getDataValue('id');
            $nazev = $this->cenik->getDataValue('nazev');
            $polozka['typZasobyK'] = $this->cenik->getDataValue('typZasobyK');

            if (empty($polozka['cenaMj'])) {
                if (!array_key_exists('cenaZaklBezDph', $polozka)) {
                    $polozka['cenaZaklBezDph'] = $this->cenik->getDataValue('cenaZaklBezDph');
                }
                if (!array_key_exists('cenaZaklVcDph', $polozka)) {
                    $polozka['cenaZaklVcDph'] = $this->cenik->getDataValue('cenaZaklVcDph');
                }
            } else {
                $polozka['cenaZaklBezDph'] = $polozka['cenaMj'];
                $polozka['cenaZaklVcDph'] = $polozka['cenaMj'] + ($polozka['cenaMj'] * 0.21);
            }
        } else {
            $nazev = $polozka['nazev'];
        }

        if (is_null($itemId)) {
            $_SESSION['cart'][] = $polozka;
            $this->addStatusMessage(
                    sprintf(_('%s added to cart'), $nazev),
                    'success'
            );
        } else {
            $_SESSION['cart'][$itemId] = $polozka;
            $this->addStatusMessage(
                    sprintf(_('%s chnged in cart'), $nazev),
                    'success'
            );
        }
    }
}
