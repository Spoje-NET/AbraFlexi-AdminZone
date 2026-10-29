<?php

declare(strict_types=1);

/**
 * AdminZone - SledovaniTV users paired with AbraFlexi addresses.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone;

class SledovaniTvExtIdService extends ExtIdService
{
    public function key(): string
    {
        return 'sltv';
    }

    public function title(): string
    {
        return _('SledovaniTV assigments');
    }

    public function columns(): array
    {
        return ['id' => _('SledovaniTV ID'), 'login' => _('SledovaniTV Login'), 'fullName' => _('SledovaniTV Name')];
    }

    public function newAccountLink(): array
    {
        return ['sltvregister.php', _('New SubClient')];
    }

    protected function accounts(): array
    {
        return (new SledovaniTV())->getUsers() ?: [];
    }

    protected function notifyService(string $serviceId, string $addressCode): void
    {
        (new SledovaniTV())->modifyUser('code:'.$addressCode, $serviceId);
    }
}
