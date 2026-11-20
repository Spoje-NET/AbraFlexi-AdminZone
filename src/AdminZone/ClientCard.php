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

namespace AdminZone;

/**
 * Card of a client: what AbraFlexi knows about the relation with one customer on one page —
 * who they are, whom to call, what they owe, what they were invoiced, what contracts they have
 * and which services they are paired with.
 *
 * build() turns records of AbraFlexi into the card and needs no connection; fetch() reads the
 * records of one customer.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
final class ClientCard
{
    /**
     * Services a client can be paired with (the keys of ExtIdService).
     */
    /**
     * External services of the installed extensions.
     *
     * @return array<string,string> key => label
     */
    public static function services(): array
    {
        return array_map(static fn (ExtIdService $service): string => $service->label(), ExtIdRegistry::all());
    }

    /**
     * Contracts ending within this many days are pointed out.
     */
    public const EXPIRING_DAYS = 60;

    /**
     * Invoices shown in the list.
     */
    public const RECENT_INVOICES = 10;

    /**
     * Read the records of a customer from AbraFlexi.
     *
     * @param string $code code (kod) of the customer in the address book
     *
     * @return null|array<string, mixed> the card or null when there is no such customer
     */
    public static function fetch(string $code, ?\DateTimeImmutable $today = null): ?array
    {
        $today ??= new \DateTimeImmutable('today');
        $address = new Adresar('code:'.$code, ['nativeTypes' => false, 'defaultUrlParams' => ['detail' => 'full']]);
        $data = $address->getData();

        if (!\is_array($data) || ($data['kod'] ?? '') === '') {
            return null;
        }

        $firm = "firma = '".self::quote('code:'.$data['kod'])."'";
        $since = $today->modify('-1 year')->format('Y-m-d');

        $contacts = (new \AbraFlexi\Kontakt(null, ['nativeTypes' => false]))
            ->getColumnsFromAbraFlexi(['id', 'jmeno', 'prijmeni', 'funkce', 'email', 'mobil', 'tel'], [$firm, 'limit' => 0, 'order' => 'prijmeni']);
        $bankAccounts = (new \AbraFlexi\AdresarBankovniUcet(null, ['nativeTypes' => false]))
            ->getColumnsFromAbraFlexi(['id', 'buc', 'smerKod', 'iban'], [$firm, 'limit' => 0]);

        $invoiceColumns = ['id', 'kod', 'datVyst', 'datSplat', 'sumCelkem', 'zbyvaUhradit'];
        $invoices = new \AbraFlexi\FakturaVydana(null, ['nativeTypes' => false]);
        $open = $invoices->getColumnsFromAbraFlexi($invoiceColumns, [$firm.' and storno = false and zbyvaUhradit > 0', 'limit' => 0, 'order' => 'datSplat']);
        $year = $invoices->getColumnsFromAbraFlexi($invoiceColumns, [$firm.' and storno = false and datVyst >= \''.$since.'\'', 'limit' => 0, 'order' => 'datVyst@D']);

        $contracts = (new \AbraFlexi\Smlouva(null, ['nativeTypes' => false]))
            ->getColumnsFromAbraFlexi(['id', 'kod', 'nazev', 'smlouvaOd', 'smlouvaDo', 'datVypoz'], [$firm, 'limit' => 0, 'order' => 'smlouvaOd@D']);

        $externalIds = [];

        foreach (array_keys(self::services()) as $service) {
            $externalIds[$service] = $address->getExternalID($service);
        }

        $card = self::build(
            $data,
            self::rows($contacts),
            self::rows($bankAccounts),
            self::rows($open),
            self::rows($year),
            self::rows($contracts),
            $externalIds,
            $today,
        );
        $card['url'] = $address->getAbraFlexiURL();

        return $card;
    }

    /**
     * Put the records of a customer together into the card.
     *
     * @param array<string, mixed>             $address      the customer (adresar)
     * @param array<int, array<string, mixed>> $contacts     contacts (kontakt)
     * @param array<int, array<string, mixed>> $bankAccounts bank accounts of the customer
     * @param array<int, array<string, mixed>> $open         unpaid issued invoices
     * @param array<int, array<string, mixed>> $year         issued invoices of the last year, the newest first
     * @param array<int, array<string, mixed>> $contracts    contracts (smlouva)
     * @param array<string, null|string>       $externalIds  id at the service by its key, null when not paired
     *
     * @return array<string, mixed>
     */
    public static function build(array $address, array $contacts, array $bankAccounts, array $open, array $year, array $contracts, array $externalIds, \DateTimeImmutable $today): array
    {
        $card = [
            'profile' => self::profile($address),
            'contacts' => self::contacts($contacts),
            'bankAccounts' => self::bankAccounts($bankAccounts),
            'receivables' => self::receivables($open, $today),
            'turnover' => 0.0,
            'invoiceCount' => \count($year),
            'invoices' => [],
            'contracts' => self::contractsOf($contracts, $today),
            'integrations' => [],
            'signals' => [],
        ];

        foreach ($year as $row) {
            $card['turnover'] += self::number($row['sumCelkem'] ?? 0);
        }

        foreach (\array_slice($year, 0, self::RECENT_INVOICES) as $row) {
            $due = self::date($row['datSplat'] ?? null);
            $left = self::number($row['zbyvaUhradit'] ?? 0);
            $card['invoices'][] = [
                'id' => (int) ($row['id'] ?? 0),
                'code' => self::text($row['kod'] ?? ''),
                'issued' => self::date($row['datVyst'] ?? null),
                'due' => $due,
                'total' => self::number($row['sumCelkem'] ?? 0),
                'left' => $left,
                'overdue' => $left > 0 && $due !== null && $due < $today->format('Y-m-d'),
            ];
        }

        foreach (self::services() as $key => $title) {
            $id = $externalIds[$key] ?? null;
            $card['integrations'][$key] = ['title' => $title, 'id' => $id !== null && $id !== '' ? (string) $id : null];
        }

        $card['signals'] = self::signals($card);

        return $card;
    }

    /**
     * What the operator should notice, the most serious first.
     *
     * @param array<string, mixed> $card
     *
     * @return array<int, array{level: string, text: string}>
     */
    private static function signals(array $card): array
    {
        $signals = [];
        $receivables = $card['receivables'];

        if ($receivables['overdueCount'] > 0) {
            $signals[] = ['level' => 'danger', 'text' => sprintf(
                _('%d invoices are overdue, %s in total; the oldest by %d days.'),
                $receivables['overdueCount'],
                self::money($receivables['overdueTotal']),
                $receivables['maxDaysOverdue'],
            )];
        }

        foreach ($card['contracts']['active'] as $contract) {
            if ($contract['expiresInDays'] !== null && $contract['expiresInDays'] <= self::EXPIRING_DAYS) {
                $signals[] = ['level' => 'warning', 'text' => sprintf(_('Contract %s ends in %d days.'), $contract['code'], max(0, $contract['expiresInDays']))];
            }
        }

        if ($card['profile']['email'] === '' && !array_filter($card['contacts'], static fn (array $contact): bool => $contact['email'] !== '')) {
            $signals[] = ['level' => 'warning', 'text' => _('There is no e-mail address to send invoices and reminders to.')];
        }

        if ($card['contracts']['active'] === [] && $card['contracts']['ended'] !== []) {
            $signals[] = ['level' => 'info', 'text' => _('All the contracts of the client have ended.')];
        }

        if ($signals === []) {
            $signals[] = ['level' => 'success', 'text' => $receivables['count'] > 0
                ? sprintf(_('Nothing is overdue; %d invoices wait for the payment.'), $receivables['count'])
                : _('Everything is paid, nothing to take care of.')];
        }

        return $signals;
    }

    /**
     * @param array<string, mixed> $address
     *
     * @return array<string, mixed>
     */
    private static function profile(array $address): array
    {
        $city = trim(self::text($address['psc'] ?? '').' '.self::text($address['mesto'] ?? ''));

        return [
            'code' => self::text($address['kod'] ?? ''),
            'name' => self::text($address['nazev'] ?? ''),
            'ic' => self::text($address['ic'] ?? ''),
            'dic' => self::text($address['dic'] ?? ''),
            'email' => self::text($address['email'] ?? ''),
            'phone' => self::text($address['mobil'] ?? '') ?: self::text($address['tel'] ?? ''),
            'address' => implode(', ', array_filter([self::text($address['ulice'] ?? ''), $city])),
            'labels' => array_values(array_filter(array_map('trim', explode(',', self::text($address['stitky'] ?? ''))))),
            'id' => (int) ($address['id'] ?? 0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array{name: string, role: string, email: string, phone: string}>
     */
    private static function contacts(array $rows): array
    {
        $contacts = [];

        foreach ($rows as $row) {
            $contacts[] = [
                'name' => trim(self::text($row['jmeno'] ?? '').' '.self::text($row['prijmeni'] ?? '')),
                'role' => self::text($row['funkce'] ?? ''),
                'email' => self::text($row['email'] ?? ''),
                'phone' => self::text($row['mobil'] ?? '') ?: self::text($row['tel'] ?? ''),
            ];
        }

        return $contacts;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, string> accounts as "number/bank code" or the IBAN
     */
    private static function bankAccounts(array $rows): array
    {
        $accounts = [];

        foreach ($rows as $row) {
            $number = self::text($row['buc'] ?? '');
            $bank = self::text($row['smerKod'] ?? '');
            $bank = (string) preg_replace('/^code:/i', '', $bank);
            $text = $number !== '' ? $number.($bank !== '' ? '/'.$bank : '') : self::text($row['iban'] ?? '');

            if ($text !== '') {
                $accounts[] = $text;
            }
        }

        return $accounts;
    }

    /**
     * @param array<int, array<string, mixed>> $open
     *
     * @return array{count: int, total: float, overdueCount: int, overdueTotal: float, oldestDue: null|string, maxDaysOverdue: int}
     */
    private static function receivables(array $open, \DateTimeImmutable $today): array
    {
        $result = ['count' => 0, 'total' => 0.0, 'overdueCount' => 0, 'overdueTotal' => 0.0, 'oldestDue' => null, 'maxDaysOverdue' => 0];
        $now = $today->format('Y-m-d');

        foreach ($open as $row) {
            $left = self::number($row['zbyvaUhradit'] ?? 0);

            if ($left <= 0) {
                continue;
            }

            ++$result['count'];
            $result['total'] += $left;
            $due = self::date($row['datSplat'] ?? null);

            if ($due !== null && $due < $now) {
                ++$result['overdueCount'];
                $result['overdueTotal'] += $left;

                if ($result['oldestDue'] === null || $due < $result['oldestDue']) {
                    $result['oldestDue'] = $due;
                }
            }
        }

        if ($result['oldestDue'] !== null) {
            $result['maxDaysOverdue'] = (int) (new \DateTimeImmutable($result['oldestDue']))->diff($today)->days;
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array{active: array<int, array<string, mixed>>, ended: array<int, array<string, mixed>>}
     */
    private static function contractsOf(array $rows, \DateTimeImmutable $today): array
    {
        $result = ['active' => [], 'ended' => []];
        $now = $today->format('Y-m-d');

        foreach ($rows as $row) {
            $from = self::date($row['smlouvaOd'] ?? null);
            $to = self::date($row['smlouvaDo'] ?? null);
            $terminated = self::date($row['datVypoz'] ?? null);
            $contract = [
                'id' => (int) ($row['id'] ?? 0),
                'code' => self::text($row['kod'] ?? ''),
                'name' => self::text($row['nazev'] ?? ''),
                'from' => $from,
                'to' => $to,
                'expiresInDays' => $to !== null ? (int) $today->diff(new \DateTimeImmutable($to))->format('%r%a') : null,
            ];

            if (($to !== null && $to < $now) || ($terminated !== null && $terminated <= $now)) {
                $result['ended'][] = $contract;
            } elseif ($from === null || $from <= $now) {
                $result['active'][] = $contract;
            }
            // a contract that has not started yet is neither active nor ended
        }

        return $result;
    }

    /**
     * Keep only the list of records (the connection gives false when nothing is found).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function rows(mixed $records): array
    {
        return \is_array($records) ? array_values(array_filter($records, '\is_array')) : [];
    }

    private static function text(mixed $value): string
    {
        if (\is_array($value)) {
            $value = $value['value'] ?? '';
        }

        return \is_scalar($value) || $value instanceof \Stringable ? trim((string) $value) : '';
    }

    private static function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Day of a date as AbraFlexi writes it ("2026-11-17+01:00") in Y-m-d or null.
     */
    private static function date(mixed $value): ?string
    {
        $day = substr(self::text($value), 0, 10);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $day);

        return $parsed !== false && $parsed->format('Y-m-d') === $day ? $day : null;
    }

    private static function quote(string $text): string
    {
        return str_replace("'", "''", $text);
    }

    /**
     * Amount in the local currency.
     */
    public static function money(float $amount): string
    {
        return number_format($amount, 2, ',', "\u{00A0}")."\u{00A0}Kč";
    }
}
