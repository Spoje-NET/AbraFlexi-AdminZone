<?php

declare(strict_types=1);

/**
 * AdminZone - IPEX customers paired with AbraFlexi addresses.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone;

class IpexExtIdService extends ExtIdService
{
    public function key(): string
    {
        return 'ipex';
    }

    public function title(): string
    {
        return _('Ipex assigments');
    }

    public function columns(): array
    {
        return ['id' => _('IpexID'), 'customerName' => _('Ipex Name')];
    }

    public function newAccountLink(): array
    {
        return ['https://b2b.ipex.cz/customer', _('New SubClient')];
    }

    protected function accounts(): array
    {
        $customers = new \IPEXB2B\Customers();
        $list = $customers->requestData();

        if ($customers->lastResponseCode !== 200) {
            throw new \RuntimeException(sprintf('IPEX answered HTTP %d (check IPEX_URL, IPEX_LOGIN and IPEX_PASSWORD)', $customers->lastResponseCode));
        }

        $rows = [];

        foreach (\is_array($list) ? $list : [] as $customer) {
            $customer['_url'] = 'https://b2b.ipex.cz/customer/dashboard/' . $customer['id'] . '&submit=Odeslat';
            $rows[] = $customer;
        }

        return $rows;
    }

    protected function notifyService(string $serviceId, string $addressCode): void
    {
        $ipexer = new \IPEXB2B\Customers();
        $ipexData = $ipexer->requestData($serviceId);

        if (\array_key_exists('customerName', $ipexData)) {
            $ipexer->setPostFields(json_encode(['customerName' => $ipexData['customerName'], 'externId' => 'code:'.$addressCode]));
            $ipexer->requestData($serviceId, 'PUT');
        } else {
            $ipexer->addStatusMessage('Assigment error for '.$addressCode, 'error');
        }
    }
}
