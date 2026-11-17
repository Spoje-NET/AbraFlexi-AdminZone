<?php

declare(strict_types=1);

/**
 * This file is part of the AdminZone package
 *
 * https://github.com/VitexSoftware/AbraFlexi-AdminZone
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AdminZone\ui;

use AdminZone\ClientCard;

/**
 * Dashboard of a client: the card of ClientCard::fetch() as a page.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class ClientCardView extends \Ease\Html\DivTag
{
    /**
     * Where the account of a client at the service can be seen.
     */
    private const SERVICE_URL = [
        'ipex' => 'https://b2b.ipex.cz/customer/dashboard/',
        'subreg' => 'https://subreg.cz/cz/users/search/?submit=Odeslat&iduser=',
        'sltv' => 'https://sledovanitv.cz/partner/user/edit/',
    ];

    /**
     * Where to pair the client with the service when it is not.
     */
    private const PAIRING = [
        'ipex' => 'extids.php?service=ipex',
        'subreg' => 'extids.php?service=subreg',
        'sltv' => 'sltvregister.php?id=code:',
    ];

    /**
     * @param array<string, mixed> $card result of ClientCard::fetch()
     */
    public function __construct(array $card)
    {
        parent::__construct(null, ['class' => 'client-card']);
        $profile = $card['profile'];

        $this->addItem($this->head($card));

        foreach ($card['signals'] as $signal) {
            $this->addItem(new \Ease\Html\DivTag(self::e($signal['text']), ['class' => 'alert alert-'.$signal['level'].' py-2 mb-2', 'role' => 'alert']));
        }

        $this->addItem($this->tiles($card));

        $row = new \Ease\Html\DivTag(null, ['class' => 'row g-3 mt-1']);
        $left = new \Ease\Html\DivTag(null, ['class' => 'col-lg-5']);
        $right = new \Ease\Html\DivTag(null, ['class' => 'col-lg-7']);

        $left->addItem($this->panel(_('Client'), $this->profileTable($profile)));
        $left->addItem($this->panel(_('Contacts'), $this->contactsTable($card['contacts'])));
        $left->addItem($this->panel(_('Services'), $this->integrations($card['integrations'], $profile['code'])));

        $right->addItem($this->panel(_('Latest invoices'), $this->invoicesTable($card['invoices'], $card['invoiceCount'])));
        $right->addItem($this->panel(_('Contracts'), $this->contractsTable($card['contracts'])));

        $row->addItem($left);
        $row->addItem($right);
        $this->addItem($row);
    }

    /**
     * @param array<string, mixed> $card
     */
    private function head(array $card): \Ease\Html\DivTag
    {
        $profile = $card['profile'];
        $labels = '';

        foreach ($profile['labels'] as $label) {
            $labels .= ' <span class="badge text-bg-secondary">'.self::e($label).'</span>';
        }

        return new \Ease\Html\DivTag(
            '<div><h1 class="h3 mb-1">'.self::e($profile['name']).'</h1>'
            .'<div class="text-muted"><code>'.self::e($profile['code']).'</code>'.$labels.'</div></div>'
            .'<div class="btn-group">'
            .'<a class="btn btn-outline-secondary btn-sm" href="adresar.php?id='.(int) $profile['id'].'"><i class="fa fa-pen"></i> '.self::e(_('Edit the address')).'</a>'
            .(($card['url'] ?? '') !== '' ? '<a class="btn btn-outline-secondary btn-sm" href="'.self::e((string) $card['url']).'" target="_blank" rel="noopener"><i class="fa fa-arrow-up-right-from-square"></i> '.self::e(_('Open in AbraFlexi')).'</a>' : '')
            .'</div>',
            ['class' => 'd-flex justify-content-between align-items-start flex-wrap gap-2 mt-4 mb-3'],
        );
    }

    /**
     * The four numbers to look at first.
     *
     * @param array<string, mixed> $card
     */
    private function tiles(array $card): \Ease\Html\DivTag
    {
        $receivables = $card['receivables'];
        $tiles = [
            [_('Unpaid'), ClientCard::money($receivables['total']), sprintf(_('%d invoices'), $receivables['count']), $receivables['count'] > 0 ? 'text-warning-emphasis' : 'text-success'],
            [_('Overdue'), ClientCard::money($receivables['overdueTotal']), $receivables['overdueCount'] > 0 ? sprintf(_('%d invoices, the oldest by %d days'), $receivables['overdueCount'], $receivables['maxDaysOverdue']) : _('nothing overdue'), $receivables['overdueCount'] > 0 ? 'text-danger' : 'text-success'],
            [_('Invoiced in 12 months'), ClientCard::money($card['turnover']), sprintf(_('%d invoices'), $card['invoiceCount']), ''],
            [_('Active contracts'), (string) \count($card['contracts']['active']), sprintf(_('%d ended'), \count($card['contracts']['ended'])), ''],
        ];
        $row = new \Ease\Html\DivTag(null, ['class' => 'row g-3']);

        foreach ($tiles as [$title, $value, $note, $class]) {
            $row->addItem(new \Ease\Html\DivTag(
                '<div class="card h-100"><div class="card-body py-3">'
                .'<div class="text-muted small">'.self::e($title).'</div>'
                .'<div class="fs-4 fw-semibold '.$class.'">'.self::e($value).'</div>'
                .'<div class="small text-muted">'.self::e($note).'</div></div></div>',
                ['class' => 'col-6 col-lg-3'],
            ));
        }

        return $row;
    }

    private function panel(string $title, string $body): \Ease\Html\DivTag
    {
        return new \Ease\Html\DivTag(
            '<div class="card-header fw-semibold">'.self::e($title).'</div><div class="card-body p-0">'.$body.'</div>',
            ['class' => 'card mb-3'],
        );
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function profileTable(array $profile): string
    {
        $email = $profile['email'];
        $phone = $profile['phone'];
        // [caption, text, ready HTML or null]: plain text is always escaped, HTML is built here only
        $rows = [
            [_('Company ID'), $profile['ic'], null],
            [_('VAT ID'), $profile['dic'], null],
            [_('Address'), $profile['address'], null],
            [_('E-mail'), $email, $email !== '' ? '<a href="mailto:'.self::e($email).'">'.self::e($email).'</a>' : null],
            [_('Phone'), $phone, $phone !== '' ? '<a href="tel:'.self::e(preg_replace('/\s+/', '', $phone) ?? '').'">'.self::e($phone).'</a>' : null],
        ];
        $html = '<table class="table table-sm mb-0"><tbody>';

        foreach ($rows as [$caption, $text, $link]) {
            $html .= '<tr><th class="fw-normal text-muted w-25">'.self::e($caption).'</th><td>'
                .($text === '' ? '<span class="text-muted">—</span>' : ($link ?? self::e($text))).'</td></tr>';
        }

        return $html.'</tbody></table>';
    }

    /**
     * @param array<int, array{name: string, role: string, email: string, phone: string}> $contacts
     */
    private function contactsTable(array $contacts): string
    {
        if ($contacts === []) {
            return self::empty(_('No contacts.'));
        }

        $html = '<ul class="list-group list-group-flush">';

        foreach ($contacts as $contact) {
            $html .= '<li class="list-group-item"><strong>'.self::e($contact['name'] ?: '—').'</strong>'
                .($contact['role'] !== '' ? ' <span class="text-muted small">'.self::e($contact['role']).'</span>' : '')
                .($contact['email'] !== '' ? '<div><i class="fa fa-envelope text-muted"></i> <a href="mailto:'.self::e($contact['email']).'">'.self::e($contact['email']).'</a></div>' : '')
                .($contact['phone'] !== '' ? '<div><i class="fa fa-phone text-muted"></i> '.self::e($contact['phone']).'</div>' : '')
                .'</li>';
        }

        return $html.'</ul>';
    }

    /**
     * @param array<string, array{title: string, id: null|string}> $integrations
     */
    private function integrations(array $integrations, string $code): string
    {
        $html = '<div class="p-3 d-flex flex-wrap gap-2">';

        foreach ($integrations as $key => $integration) {
            if ($integration['id'] !== null) {
                $html .= '<a class="btn btn-success btn-sm" href="'.self::e(self::SERVICE_URL[$key].rawurlencode($integration['id'])).'" target="_blank" rel="noopener" title="'.self::e(_('Paired').': '.$integration['id']).'"><i class="fa fa-check"></i> '.self::e($integration['title']).'</a>';
            } else {
                $href = self::PAIRING[$key].($key === 'sltv' ? rawurlencode($code) : '');
                $html .= '<a class="btn btn-outline-secondary btn-sm" href="'.self::e($href).'" title="'.self::e(_('Not paired yet')).'"><i class="fa fa-link"></i> '.self::e($integration['title']).'</a>';
            }
        }

        return $html.'</div>';
    }

    /**
     * @param array<int, array<string, mixed>> $invoices
     */
    private function invoicesTable(array $invoices, int $count): string
    {
        if ($invoices === []) {
            return self::empty(_('Nothing was invoiced in the last 12 months.'));
        }

        $html = '<div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr>'
            .'<th>'.self::e(_('Invoice')).'</th><th>'.self::e(_('Issued')).'</th><th>'.self::e(_('Due')).'</th>'
            .'<th class="text-end">'.self::e(_('Total')).'</th><th class="text-end">'.self::e(_('To pay')).'</th></tr></thead><tbody>';

        foreach ($invoices as $invoice) {
            $html .= '<tr'.($invoice['overdue'] ? ' class="table-danger"' : '').'>'
                .'<td>'.self::e($invoice['code']).'</td>'
                .'<td>'.self::e(self::day($invoice['issued'])).'</td>'
                .'<td>'.self::e(self::day($invoice['due'])).'</td>'
                .'<td class="text-end">'.self::e(ClientCard::money($invoice['total'])).'</td>'
                .'<td class="text-end">'.($invoice['left'] > 0 ? '<strong>'.self::e(ClientCard::money($invoice['left'])).'</strong>' : '<span class="text-success"><i class="fa fa-check"></i></span>').'</td></tr>';
        }

        $html .= '</tbody></table></div>';

        if ($count > \count($invoices)) {
            $html .= '<div class="small text-muted p-2">'.self::e(sprintf(_('%d of %d invoices of the last 12 months'), \count($invoices), $count)).'</div>';
        }

        return $html;
    }

    /**
     * @param array{active: array<int, array<string, mixed>>, ended: array<int, array<string, mixed>>} $contracts
     */
    private function contractsTable(array $contracts): string
    {
        if ($contracts['active'] === [] && $contracts['ended'] === []) {
            return self::empty(_('No contracts.'));
        }

        $html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>'
            .'<th>'.self::e(_('Contract')).'</th><th>'.self::e(_('Name')).'</th><th>'.self::e(_('From')).'</th><th>'.self::e(_('To')).'</th><th></th></tr></thead><tbody>';

        foreach (['active' => 'success', 'ended' => 'secondary'] as $state => $badge) {
            foreach ($contracts[$state] as $contract) {
                $expiring = $state === 'active' && $contract['expiresInDays'] !== null && $contract['expiresInDays'] <= ClientCard::EXPIRING_DAYS;
                $html .= '<tr class="'.($state === 'ended' ? 'text-muted' : '').'">'
                    .'<td>'.self::e($contract['code']).'</td><td>'.self::e($contract['name']).'</td>'
                    .'<td>'.self::e(self::day($contract['from'])).'</td><td>'.self::e($contract['to'] !== null ? self::day($contract['to']) : _('open-ended')).'</td>'
                    .'<td><span class="badge text-bg-'.($expiring ? 'warning' : $badge).'">'.self::e($state === 'ended' ? _('ended') : ($expiring ? _('ends soon') : _('active'))).'</span></td></tr>';
            }
        }

        return $html.'</tbody></table></div>';
    }

    private function empty(string $text): string
    {
        return '<div class="p-3 text-muted">'.self::e($text).'</div>';
    }

    /**
     * 2026-11-17 as 17. 11. 2026.
     */
    private static function day(?string $date): string
    {
        return $date === null ? '—' : (new \DateTimeImmutable($date))->format('j. n. Y');
    }

    private static function e(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }
}
