<?php

declare(strict_types=1);

/**
 * AdminZone - Subreg users paired with AbraFlexi addresses.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone\extid;

class SubregExtIdService extends \AdminZone\ExtIdService
{
    public function key(): string
    {
        return 'subreg';
    }

    public function label(): string
    {
        return 'Subreg';
    }

    public function icon(): string
    {
        return 'fa-globe';
    }

    public function accountUrl(string $serviceId): ?string
    {
        return 'https://subreg.cz/cz/users/search/?submit=Odeslat&iduser=' . rawurlencode($serviceId);
    }

    public function registerLink(string $addressId): ?array
    {
        return ['adr2subreg.php?adrid=' . rawurlencode($addressId), _('Register Contact On Subreg')];
    }

    public function expectedProducts(): array
    {
        return ['KREDIT_DOMENA'];
    }

    public function creditProducts(): array
    {
        return ['KREDIT_DOMENA'];
    }

    public function title(): string
    {
        return _('Subreg assigments');
    }

    public function columns(): array
    {
        return [
            'id' => _('SubregID'),
            'username' => _('Subreg Username'),
            'name' => _('Subreg Name'),
            'company_id' => _('Company ID'),
            'credit' => _('Subreg Credit'),
        ];
    }

    public function newAccountLink(): array
    {
        return ['https://subreg.cz/cz/users/new', _('New SubClient')];
    }

    protected function accounts(): array
    {
        $subreger = new SubregAPI();
        $subreger->login();
        $userListRaw = $subreger->call('Users_List');
        $rows = [];

        if (!\array_key_exists('error', $userListRaw) && (int) $userListRaw['count']) {
            foreach ($userListRaw['users'] as $user) {
                $user['credit'] = $user['credit'].' '.$user['currency'];
                $user['_url'] = 'https://subreg.cz/cz/users/search/?iduser='.$user['id'].'&submit=Odeslat';
                $rows[] = $user;
            }
        }

        return $rows;
    }
}
