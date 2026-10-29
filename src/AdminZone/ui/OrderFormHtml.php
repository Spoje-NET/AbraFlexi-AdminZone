<?php

namespace AdminZone\ui;

/**
 * AdminZone - OrderForm class
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2017 Spoje.Net
 */
class OrderFormHtml extends \Ease\TWB\Form
{
    /**
     * Order object
     * @var \AdminZone\Order
     */
    public $order = null;

    /**
     * Credit Request Form
     *
     * @param \AdminZone\OrderItem $order    Form Identificaton
     */
    public function __construct($order)
    {
        $this->order = $order;
        parent::__construct(['name' => 'OrderForm']);
    }

    /**
     * Draw Form
     */
    public function finalize(): void
    {
        $service = $this->order->getDataValue('service');
        $this->addInput($this->productList(), _('Service Requested'));
        if (empty($service)) {
            $this->addItem(new \Ease\TWB\SubmitButton(
                _('Enter details'),
                'success'
            ));
        } else {
            if (is_array($this->order->plugins) && array_key_exists($service, $this->order->plugins) && is_object($this->order->plugins[$service])) {
                $this->order->plugins[$service]->formFields($this);
            }
            $this->addItem(new \Ease\TWB\SubmitButton(_('Order Now'), 'success'));
        }
        parent::finalize();
    }

    /**
     * Product list select
     *
     * @return \Ease\Html\SelectTag
     */
    public function productList()
    {
        $serviceMenu = ['' => _('Please choose your service')];
        $user = \Ease\Shared::user();

        foreach ($this->order->plugins as $className => $itemclass) {
            if ($user->getUserID() && isset($itemclass->reqExtPrefix)) {
                $prefixID = $user->getAdresar()->getExternalID($itemclass->reqExtPrefix);
                if (empty($prefixID)) {
                    continue;
                }
            }
            $serviceMenu[$className] = $itemclass->name;
        }

        /*
          'PROVOZVOIPTELEFONU' => _('VoIP'),
          'KREDIT_DOMENY' => _('Domain Credit'),
         */
        return new \Ease\Html\SelectTag(
            'service',
            $serviceMenu,
            (string)$this->order->getDataValue('service')
        );
    }
}
