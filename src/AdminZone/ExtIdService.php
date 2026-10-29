<?php

declare(strict_types=1);

/**
 * AdminZone - external service whose accounts are paired with AbraFlexi addresses.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone;

/**
 * Service (IPEX, Subreg, SledovaniTV) accounts paired to AbraFlexi customers
 * by external ID ext:<key>:<service account id>.
 */
abstract class ExtIdService
{
    /**
     * Known services.
     *
     * @var array<string,class-string<ExtIdService>>
     */
    public static array $services = [
        'ipex' => IpexExtIdService::class,
        'subreg' => SubregExtIdService::class,
        'sltv' => SledovaniTvExtIdService::class,
    ];

    public static function create(string $key): ?self
    {
        return \array_key_exists($key, self::$services) ? new self::$services[$key]() : null;
    }

    /**
     * Prefix of external ID: ext:<key>:<id>.
     */
    abstract public function key(): string;

    abstract public function title(): string;

    /**
     * Columns of the grid except the AbraFlexi one.
     *
     * @return array<string,string> row field => column title
     */
    abstract public function columns(): array;

    /**
     * Accounts at the service.
     *
     * @return array<int,array<string,mixed>> rows with at least 'id'; optional '_url'
     */
    abstract protected function accounts(): array;

    /**
     * Let the service know to which AbraFlexi customer the account belongs.
     */
    protected function notifyService(string $serviceId, string $addressCode): void
    {
    }

    /**
     * Link to create a new account at the service.
     *
     * @return array{0:string,1:string} url and caption
     */
    abstract public function newAccountLink(): array;

    /**
     * Rows for the grid with assigned AbraFlexi address (_code, _label).
     *
     * @return array<int,array<string,mixed>>
     */
    public function items(): array
    {
        $assigned = $this->assignedAddresses();
        $perAccountLookup = $assigned === null;
        $assigned ??= [];
        $rows = [];

        $visible = array_flip(array_merge(array_keys($this->columns()), ['id', '_url']));

        foreach ($this->accounts() as $account) {
            // pass only displayed fields to the page (services also return logins and passwords)
            $account = array_intersect_key($account, $visible);
            $id = (string) $account['id'];
            $account['id'] = $id;

            if ($perAccountLookup) {
                $assigned[$id] = $this->lookupAddress($id);
            }

            $account['_code'] = $assigned[$id]['kod'] ?? '';
            $account['_label'] = isset($assigned[$id]) ? $assigned[$id]['kod'].' — '.$assigned[$id]['nazev'] : '';
            $rows[] = $account;
        }

        return $rows;
    }

    /**
     * Pair service account with AbraFlexi address.
     *
     * @param string $serviceId   account id at the service
     * @param string $addressCode AbraFlexi address code (kod)
     */
    public function assign(string $serviceId, string $addressCode): bool
    {
        $adresar = new \AbraFlexi\Adresar();
        $code = \AbraFlexi\Functions::code($addressCode);

        if (!$adresar->recordExists(['id' => $code])) {
            return false;
        }

        $adresar->changeExternalID($this->key(), $serviceId, $code);

        if ($adresar->lastResponseCode !== 201 && $adresar->lastResponseCode !== 200) {
            return false;
        }

        $this->notifyService($serviceId, $addressCode);

        return true;
    }

    /**
     * AbraFlexi address paired with one service account (slow, one request).
     *
     * @return null|array{kod:string,nazev:string}
     */
    protected function lookupAddress(string $serviceId): ?array
    {
        $adresar = new \AbraFlexi\Adresar();
        $adresar->ignore404(true);
        $adresar->loadFromAbraFlexi('ext:'.$this->key().':'.$serviceId);

        return $adresar->lastResponseCode === 200 ? ['kod' => \AbraFlexi\Functions::uncode($adresar->getRecordCode()), 'nazev' => (string) $adresar->getDataValue('nazev')] : null;
    }

    /**
     * Service account id => AbraFlexi address (kod, nazev) already paired.
     *
     * @return null|array<string,array{kod:string,nazev:string}> null when AbraFlexi did not return external ids
     */
    protected function assignedAddresses(): ?array
    {
        $adresar = new \AbraFlexi\Adresar(null, ['limit' => 0]);
        $adresar->defaultUrlParams['add-external-ids'] = 'true';
        $prefix = 'ext:'.$this->key().':';
        $assigned = [];
        $supported = false;

        foreach ($adresar->getColumnsFromAbraFlexi(['kod', 'nazev', 'external-ids'], ['limit' => 0]) ?: [] as $address) {
            $supported = $supported || \array_key_exists('external-ids', $address);

            foreach ((array) ($address['external-ids'] ?? []) as $extId) {
                $extId = (string) $extId;

                if (str_starts_with($extId, $prefix)) {
                    $assigned[substr($extId, \strlen($prefix))] = ['kod' => (string) $address['kod'], 'nazev' => (string) $address['nazev']];
                }
            }
        }

        return $supported ? $assigned : null;
    }
}
