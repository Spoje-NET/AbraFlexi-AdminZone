<?php

declare(strict_types=1);

/**
 * AdminZone - datagrid pairing service accounts with AbraFlexi addresses.
 *
 * @copyright  2018-2026 Spoje.Net
 */

namespace AdminZone\ui;

/**
 * Datagrid (DataTables) listing service accounts; each row has an AbraFlexi
 * address picker with suggestions. Chosen address is paired immediately.
 */
class ExtIdGrid extends \Ease\Html\TableTag
{
    /**
     * @var array<int,array<string,mixed>>
     */
    private array $rows = [];
    private ?string $error = null;

    /**
     * @param array{id?:string,code?:string} $prefill proposed pairing to confirm
     */
    public function __construct(private \AdminZone\ExtIdService $service, private array $prefill = [])
    {
        parent::__construct(null, ['class' => 'table table-bordered', 'id' => 'extidgrid', 'style' => 'width:100%']);

        try {
            $this->rows = $service->items();
        } catch (\Throwable $exc) {
            $detail = sprintf('%s (code %s)', $exc->getMessage(), (string) $exc->getCode());

            if ($exc instanceof \AbraFlexi\Exception) {
                $detail .= ' response: ' . substr(trim($exc->getServerResponse()), 0, 300);
            }

            $this->error = $detail;
            error_log(sprintf('AdminZone %s accounts could not be loaded: %s', $service->key(), $detail));
        }
    }

    public function finalize(): void
    {
        if ($this->error !== null) {
            $debug = filter_var(\Ease\Shared::cfg('APP_DEBUG', false), \FILTER_VALIDATE_BOOLEAN);
            $this->addItem(new \Ease\Html\DivTag(
                sprintf(_('Accounts of %s could not be loaded. Check the connection settings in the configuration file.'), $this->service->title()) . ($debug ? ' ' . $this->error : ''),
                ['class' => 'alert alert-danger'],
            ));

            $this->addItem($this->companyHint());

            return;
        }

        // jQuery is loaded by the page; DataTables and Select2 come from CDN (Bootstrap 5 skins)
        $this->includeCss('https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css');
        $this->includeJavaScript('https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js');
        $this->includeJavaScript('https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js');
        $this->includeCss('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        $this->includeCss('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');
        $this->includeJavaScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js');

        $columns = [];

        foreach ($this->service->columns() as $field => $title) {
            $columns[] = ['data' => $field, 'title' => $title, 'defaultContent' => '', 'isId' => $field === 'id'];
        }

        $flags = \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT | \JSON_UNESCAPED_UNICODE;
        $this->addJavaScript('
var extIdService = '.json_encode($this->service->key(), $flags).';
var extIdColumns = '.json_encode($columns, $flags).';
var extIdRows = '.json_encode($this->rows, $flags).';
var extIdPrefill = '.json_encode($this->prefill, $flags).';
var extIdTitles = '.json_encode(['address' => _('AbraFlexi Address'), 'placeholder' => _('Type code or name of the customer'), 'only' => _('Only unassigned'), 'confirm' => _('Confirm')], $flags).';
'.file_get_contents(__DIR__.'/../../js/extidgrid.js'));
        parent::finalize();
    }

    /**
     * Explain which company is used and which ones the operator can reach.
     */
    private function companyHint(): \Ease\Html\DivTag
    {
        $user = \Ease\Shared::user();
        $used = (string) \Ease\Shared::cfg('ABRAFLEXI_COMPANY');
        $available = $user instanceof \AdminZone\User ? $user->availableCompanies() : [];
        $text = sprintf(_('Configured company: %s.'), '<strong>' . htmlspecialchars($used) . '</strong>');

        if ($available === []) {
            $text .= ' ' . _('The list of companies of this AbraFlexi server could not be loaded.');
        } elseif (!isset($available[$used])) {
            $list = [];

            foreach ($available as $code => $name) {
                $list[] = '<code>' . htmlspecialchars($code) . '</code> (' . htmlspecialchars($name) . ')';
            }

            $text .= ' ' . _('It is not among the companies available to your account:') . ' ' . implode(', ', $list)
                . '. ' . _('Set ABRAFLEXI_COMPANY in the configuration file.');
        }

        return new \Ease\Html\DivTag($text, ['class' => 'alert alert-warning mt-3']);
    }
}
